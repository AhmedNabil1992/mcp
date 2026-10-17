<?php

declare(strict_types=1);

namespace Webkul\Mcp\Tools\Admin\Business;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use InvalidArgumentException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Webkul\Mcp\Support\BusinessDataCatalog;
use Webkul\Mcp\Support\BusinessDataQueryService;

#[IsReadOnly]
#[IsIdempotent]
final class BusinessDataCatalogTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Discover the allowlisted business entities and their real database columns.
        Use this before query_business_data when no dedicated metric answers the user's question.
        Call without an entity for the entity list, then call with an entity for its columns.
    MARKDOWN;

    public function __construct(private readonly BusinessDataQueryService $queryService) {}

    public function name(): string
    {
        return 'business_data_catalog';
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'entity' => ['nullable', 'string', 'in:'.implode(',', array_keys(BusinessDataCatalog::entities()))],
        ]);

        try {
            return Response::json($this->queryService->catalog($validated['entity'] ?? null));
        } catch (InvalidArgumentException $exception) {
            return Response::error($exception->getMessage());
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'entity' => $schema->string()->description('Optional entity name. Omit it to list all queryable entities.'),
        ];
    }
}
