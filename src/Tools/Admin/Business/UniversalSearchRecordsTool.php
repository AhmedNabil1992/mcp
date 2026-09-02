<?php

namespace Webkul\Mcp\Tools\Admin\Business;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Webkul\Mcp\Support\UniversalQueryService;

#[IsReadOnly]
#[IsIdempotent]
class UniversalSearchRecordsTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Search across core AureusERP records directly by keyword.
        Supports entities: "orders", "invoices", "products", "partners", "employees", "tickets", "projects".
    MARKDOWN;

    public function __construct(protected UniversalQueryService $universalQueryService) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'entity' => ['required', 'string', 'in:orders,invoices,products,partners,employees,tickets,projects'],
            'query'  => ['nullable', 'string', 'max:200'],
            'limit'  => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $limit = (int) ($validated['limit'] ?? 10);
        $payload = $this->universalQueryService->search(
            $validated['entity'],
            $validated['query'] ?? '',
            $limit
        );

        if (isset($payload['error'])) {
            return Response::error((string) $payload['error']);
        }

        return Response::json($payload);
    }

    /**
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'entity' => $schema->string()
                ->description('The target ERP entity: "orders", "invoices", "products", "partners", "employees", "tickets", "projects".')
                ->required(),
            'query' => $schema->string()
                ->description('Search keywords, e.g. customer name, invoice number, product SKU/name, employee name.'),
            'limit' => $schema->integer()
                ->description('Maximum number of records to return (1-50, default 10).')
                ->default(10),
        ];
    }
}
