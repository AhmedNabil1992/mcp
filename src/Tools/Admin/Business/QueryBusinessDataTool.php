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
final class QueryBusinessDataTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Run a safe read-only list or aggregate query against an allowlisted AureusERP business entity.
        Supports count, sum, avg, min, max, grouping and structured filters. Never accepts SQL.
        Inspect business_data_catalog first to get exact entity and column names.
    MARKDOWN;

    public function __construct(private readonly BusinessDataQueryService $queryService) {}

    public function name(): string
    {
        return 'query_business_data';
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'entity'             => ['required', 'string', 'in:'.implode(',', array_keys(BusinessDataCatalog::entities()))],
            'operation'          => ['nullable', 'string', 'in:list,count,sum,avg,min,max'],
            'metric_column'      => ['nullable', 'string', 'max:100'],
            'group_by'           => ['nullable', 'string', 'max:100'],
            'filters'            => ['nullable', 'array', 'max:20'],
            'filters.*'          => ['array:column,operator,value'],
            'filters.*.column'   => ['required', 'string', 'max:100'],
            'filters.*.operator' => ['nullable', 'string', 'in:=,!=,>,>=,<,<=,like,not_like,in,not_in,between,is_null,not_null'],
            'columns'            => ['nullable', 'array', 'max:30'],
            'columns.*'          => ['string', 'max:100'],
            'order_by'           => ['nullable', 'string', 'max:100'],
            'direction'          => ['nullable', 'string', 'in:asc,desc'],
            'limit'              => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            return Response::json($this->queryService->query(
                entity: $validated['entity'],
                operation: $validated['operation'] ?? 'list',
                metricColumn: $validated['metric_column'] ?? null,
                groupBy: $validated['group_by'] ?? null,
                filters: $validated['filters'] ?? [],
                columns: $validated['columns'] ?? [],
                orderBy: $validated['order_by'] ?? null,
                direction: $validated['direction'] ?? 'desc',
                limit: (int) ($validated['limit'] ?? 25),
            ));
        } catch (InvalidArgumentException $exception) {
            return Response::error($exception->getMessage());
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'entity'        => $schema->string()->description('Exact entity name from business_data_catalog.')->required(),
            'operation'     => $schema->string()->enum(['list', 'count', 'sum', 'avg', 'min', 'max'])->default('list'),
            'metric_column' => $schema->string()->description('Required column for sum, avg, min or max.'),
            'group_by'      => $schema->string()->description('Optional column used to group an aggregate.'),
            'filters'       => $schema->array()->items($schema->object([
                'column'   => $schema->string()->required(),
                'operator' => $schema->string()->enum(['=', '!=', '>', '>=', '<', '<=', 'like', 'not_like', 'in', 'not_in', 'between', 'is_null', 'not_null'])->default('='),
                'value'    => $schema->string()->description('Filter value. Use separate >= and <= filters for date or numeric ranges.'),
            ])->withoutAdditionalProperties())->max(20),
            'columns'   => $schema->array()->items($schema->string())->max(30)->description('Columns returned by list operations.'),
            'order_by'  => $schema->string(),
            'direction' => $schema->string()->enum(['asc', 'desc'])->default('desc'),
            'limit'     => $schema->integer()->min(1)->max(100)->default(25),
        ];
    }
}
