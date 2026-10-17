<?php

namespace Webkul\Mcp\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Laravel\Mcp\Server\Tool;
use RuntimeException;
use Throwable;
use Webkul\Mcp\Servers\ErpAgentServer;
use Webkul\PluginManager\Models\Plugin;

class GeminiAssistantService
{
    protected ?string $apiKey;

    protected string $model;

    protected string $liveModel;

    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct(
        protected BusinessToolService $businessToolService,
        protected UniversalQueryService $universalQueryService,
        protected BusinessDataQueryService $businessDataQueryService,
    ) {
        $this->apiKey = config('services.gemini.api_key') ?: env('GEMINI_API_KEY');
        $this->model = config('services.gemini.model') ?: env('GEMINI_MODEL', 'gemini-2.5-flash');
        $this->liveModel = config('services.gemini.live_model') ?: env('GEMINI_LIVE_MODEL', 'gemini-3.1-flash-live-preview');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getLiveModel(): string
    {
        return $this->liveModel;
    }

    /**
     * @return array{token:string,model:string,tools:array<int, array<string, mixed>>,system_instruction:string}
     */
    public function createLiveSession(): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Gemini API Key is not configured.');
        }

        $response = Http::acceptJson()
            ->asJson()
            ->withHeader('x-goog-api-key', $this->apiKey)
            ->timeout(30)
            ->post('https://generativelanguage.googleapis.com/v1beta/auth_tokens', [
                'uses'                 => 1,
                'expireTime'           => now()->addMinutes(15)->utc()->format('Y-m-d\TH:i:s\Z'),
                'newSessionExpireTime' => now()->addMinute()->utc()->format('Y-m-d\TH:i:s\Z'),
            ]);

        if (! $response->successful() || blank($response->json('name'))) {
            throw new RuntimeException('Unable to create a secure Gemini Live session: '.($response->json('error.message') ?? $response->body()));
        }

        return [
            'token'              => (string) $response->json('name'),
            'model'              => $this->liveModel,
            'tools'              => $this->getGeminiToolDeclarations(),
            'system_instruction' => $this->systemInstruction(),
        ];
    }

    public function executeRegisteredTool(string $name, array $arguments = []): mixed
    {
        $registeredNames = collect($this->getGeminiToolDeclarations())->pluck('name');

        if (! $registeredNames->contains(str_replace('-', '_', $name))) {
            throw new RuntimeException("Unregistered MCP tool [{$name}].");
        }

        return $this->executeTool($name, $arguments);
    }

    /**
     * Send a conversation history to Gemini with tool definitions and handle tool calling.
     *
     * @param  array<int, array{role: string, content: string}>  $history
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
                ['text' => $this->systemInstruction()],
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
                $endpoint = "{$this->baseUrl}/{$this->model}:generateContent";
                $response = Http::acceptJson()
                    ->asJson()
                    ->withHeader('x-goog-api-key', $this->apiKey)
                    ->timeout((int) config('services.gemini.timeout', 120))
                    ->retry(2, 500)
                    ->post($endpoint, $body);

                if (! $response->successful()) {
                    Log::error('Gemini API error', [
                        'status' => $response->status(),
                        'body'   => $response->body(),
                    ]);

                    return [
                        'content' => '',
                        'error'   => 'Gemini API error ('.$response->status().'): '.($response->json('error.message') ?? $response->body()),
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
                Log::error('Gemini request exception: '.$e->getMessage());

                return [
                    'content' => '',
                    'error'   => 'Exception calling Gemini AI: '.$e->getMessage(),
                ];
            }
        }

        return [
            'content' => 'Max tool calling iterations reached.',
        ];
    }

    public function systemInstruction(): string
    {
        return "You are AureusERP AI Assistant, an expert ERP intelligence agent.
You answer questions about every installed AureusERP plugin. Use installed_plugins_overview whenever plugin availability or MCP coverage is unclear.
You have access to read-only ERP tools. ALWAYS use tools to fetch exact, real-time facts and figures before answering and never claim that data was changed.
Prefer a dedicated metric when one exists. For other factual data questions, inspect business_data_catalog and then use query_business_data with exact column names.
Respect the authenticated user's company and record visibility. Distinguish customer receivables from customer prepaid balances.
For monthly sales, invoices, revenue, or profitability, use monthly_sales_profitability. It includes sales-order invoices and direct invoices; sales_order_insights is only order intake and must not be presented as complete invoiced sales.
When asked how many licenses were made or activated this month, report software_licenses_overview.current_month.activated_count, not created_count or total_licenses.
For current account balances, use accounting_cashflow_snapshot and report cash/bank, customer prepaid balance, receivables, and payables separately.
Respond politely, concisely, and format numbers, currencies, and lists cleanly in markdown.
Reply in the same language as the user's message (Arabic or English). For spoken Arabic, use clear natural Arabic and understand Egyptian Arabic.";
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
                /** @var Tool $tool */
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
                Log::warning("Failed to inspect tool [{$toolClass}] for Gemini: ".$e->getMessage());
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

        if ($cleanName === 'business_data_catalog') {
            return $this->businessDataQueryService->catalog($arguments['entity'] ?? null);
        }

        if ($cleanName === 'query_business_data') {
            return $this->businessDataQueryService->query(
                entity: (string) ($arguments['entity'] ?? ''),
                operation: (string) ($arguments['operation'] ?? 'list'),
                metricColumn: isset($arguments['metric_column']) ? (string) $arguments['metric_column'] : null,
                groupBy: isset($arguments['group_by']) ? (string) $arguments['group_by'] : null,
                filters: is_array($arguments['filters'] ?? null) ? $arguments['filters'] : [],
                columns: is_array($arguments['columns'] ?? null) ? $arguments['columns'] : [],
                orderBy: isset($arguments['order_by']) ? (string) $arguments['order_by'] : null,
                direction: (string) ($arguments['direction'] ?? 'desc'),
                limit: (int) ($arguments['limit'] ?? 25),
            );
        }

        if ($cleanName === 'installed_plugins_overview') {
            if (! Schema::hasTable('plugins')) {
                return ['error' => 'Plugin metadata table is unavailable.'];
            }

            return [
                'installed_plugins' => Plugin::query()
                    ->where('is_installed', true)
                    ->orderBy('name')
                    ->get(['name', 'summary', 'latest_version', 'is_active'])
                    ->map(fn (Plugin $plugin): array => [
                        'name'    => $plugin->name,
                        'active'  => (bool) $plugin->is_active,
                        'version' => $plugin->latest_version,
                        'summary' => $plugin->summary,
                    ])->all(),
                'available_mcp_tools' => collect($this->getGeminiToolDeclarations())->pluck('name')->all(),
            ];
        }

        return $this->businessToolService->run($cleanName);
    }
}
