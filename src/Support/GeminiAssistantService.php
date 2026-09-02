<?php

namespace Webkul\Mcp\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\Mcp\Servers\ErpAgentServer;

class GeminiAssistantService
{
    protected ?string $apiKey;
    protected string $model;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct(
        protected BusinessToolService $businessToolService,
        protected UniversalQueryService $universalQueryService,
    ) {
        $this->apiKey = config('services.gemini.api_key') ?: env('GEMINI_API_KEY');
        $this->model = config('services.gemini.model') ?: env('GEMINI_MODEL', 'gemini-2.5-flash');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }

    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Send a conversation history to Gemini with tool definitions and handle tool calling.
     *
     * @param array<int, array{role: string, content: string}> $history
     * @return array{content: string, error?: string}
     */
    public function chat(array $history): array
    {
        if (! $this->isConfigured()) {
            return [
                'content' => '',
                'error'   => 'Gemini API Key is not configured. Please set GEMINI_API_KEY in your .env file.',
            ];
        }

        // Build messages in Gemini format
        $contents = [];
        foreach ($history as $msg) {
            $role = ($msg['role'] === 'user') ? 'user' : 'model';
            $contents[] = [
                'role'  => $role,
                'parts' => [
                    ['text' => $msg['content']],
                ],
            ];
        }

        $systemInstruction = [
            'parts' => [
                ['text' => "You are AureusERP AI Assistant, an expert ERP intelligence agent. 
You answer user questions about sales, purchases, invoices, accounting, inventory, projects, employees, recruitment, partners, products, and technical support.
You have access to ERP tools. ALWAYS use tools to fetch exact, real-time facts and figures from the ERP before answering.
Respond politely, concisely, and format numbers, currencies, and lists cleanly in markdown.
Reply in the same language as the user's message (Arabic or English)."],
            ],
        ];

        $tools = $this->getGeminiToolDeclarations();

        $maxIterations = 5;
        $iteration = 0;

        while ($iteration < $maxIterations) {
            $iteration++;

            $body = [
                'systemInstruction' => $systemInstruction,
                'contents'          => $contents,
                'tools'             => [
                    [
                        'functionDeclarations' => $tools,
                    ],
                ],
            ];

            try {
                $endpoint = "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}";
                $response = Http::timeout(45)->post($endpoint, $body);

                if (! $response->successful()) {
                    Log::error('Gemini API error', [
                        'status' => $response->status(),
                        'body'   => $response->body(),
                    ]);

                    return [
                        'content' => '',
                        'error'   => 'Gemini API error (' . $response->status() . '): ' . ($response->json('error.message') ?? $response->body()),
                    ];
                }

                $candidate = $response->json('candidates.0.content');
                if (! $candidate || ! isset($candidate['parts'])) {
                    return [
                        'content' => 'No response generated from AI.',
                    ];
                }

                $parts = $candidate['parts'];
                $hasFunctionCall = false;
                $functionCalls = [];
                $textResponse = '';

                foreach ($parts as $part) {
                    if (isset($part['functionCall'])) {
                        $hasFunctionCall = true;
                        $functionCalls[] = $part['functionCall'];
                    }
                    if (isset($part['text'])) {
                        $textResponse .= $part['text'];
                    }
                }

                // If Gemini called one or more tools/functions
                if ($hasFunctionCall) {
                    // Sanitize model parts: in Gemini REST API, function_call.args must be a JSON object ({}), never an empty JSON array ([]).
                    $sanitizedModelParts = array_map(function ($p) {
                        if (isset($p['functionCall'])) {
                            $args = $p['functionCall']['args'] ?? [];
                            $p['functionCall']['args'] = empty($args) ? (object) [] : (object) $args;
                        }
                        return $p;
                    }, $parts);

                    // Append the model's turn to contents
                    $contents[] = [
                        'role'  => 'model',
                        'parts' => $sanitizedModelParts,
                    ];

                    // Execute each function call and build response parts
                    $responseParts = [];
                    foreach ($functionCalls as $fnCall) {
                        $fnName = $fnCall['name'];
                        $fnArgs = (array) ($fnCall['args'] ?? []);

                        // Execute tool
                        $toolResult = $this->executeTool($fnName, $fnArgs);

                        $responseParts[] = [
                            'functionResponse' => [
                                'name'     => $fnName,
                                'response' => [
                                    'content' => is_array($toolResult) ? $toolResult : ['data' => $toolResult],
                                ],
                            ],
                        ];
                    }

                    // Append user's functionResponse turn
                    $contents[] = [
                        'role'  => 'user',
                        'parts' => $responseParts,
                    ];

                    // Loop again so model can see tool output
                    continue;
                }

                return [
                    'content' => $textResponse ?: 'I have processed the request.',
                ];
            } catch (Throwable $e) {
                Log::error('Gemini request exception: ' . $e->getMessage());

                return [
                    'content' => '',
                    'error'   => 'Exception calling Gemini AI: ' . $e->getMessage(),
                ];
            }
        }

        return [
            'content' => 'Max tool calling iterations reached.',
        ];
    }

    /**
     * Dynamically convert all tools registered on ErpAgentServer into Gemini function declarations.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getGeminiToolDeclarations(): array
    {
        $toolClasses = ErpAgentServer::getRegisteredTools();
        $declarations = [];

        foreach ($toolClasses as $toolClass) {
            try {
                /** @var \Laravel\Mcp\Server\Tool $tool */
                $tool = app($toolClass);
                $array = $tool->toArray();

                $name = $array['name'];
                $description = $array['description'] ?? $array['title'] ?? $name;
                $inputSchema = $array['inputSchema'] ?? [];

                // Convert schema to Gemini parameters format
                $parameters = [
                    'type'       => 'object',
                    'properties' => $inputSchema['properties'] ?? (object) [],
                ];

                if (! empty($inputSchema['required'])) {
                    $parameters['required'] = $inputSchema['required'];
                }

                $declarations[] = [
                    'name'        => str_replace('-', '_', $name),
                    'description' => trim(strip_tags((string) $description)),
                    'parameters'  => $parameters,
                ];
            } catch (Throwable $e) {
                Log::warning("Failed to inspect tool [{$toolClass}] for Gemini: " . $e->getMessage());
            }
        }

        return $declarations;
    }

    /**
     * Execute tool dynamically by name or fallback to universal search.
     */
    protected function executeTool(string $name, array $arguments): mixed
    {
        $cleanName = str_replace('-', '_', $name);

        if ($cleanName === 'universal_search_records') {
            $entity = $arguments['entity'] ?? 'products';
            $query = $arguments['query'] ?? '';
            $limit = (int) ($arguments['limit'] ?? 10);

            return $this->universalQueryService->search($entity, $query, $limit);
        }

        return $this->businessToolService->run($cleanName);
    }
}
