<?php

declare(strict_types=1);

namespace Webkul\Mcp\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

final class BusinessDataQueryService
{
    private const OPERATIONS = ['list', 'count', 'sum', 'avg', 'min', 'max'];

    private const FILTER_OPERATORS = ['=', '!=', '>', '>=', '<', '<=', 'like', 'not_like', 'in', 'not_in', 'between', 'is_null', 'not_null'];

    public function catalog(?string $entity = null): array
    {
        $entities = BusinessDataCatalog::entities();

        if ($entity === null || $entity === '') {
            return [
                'entities' => collect($entities)->map(fn (array $definition, string $name): array => [
                    'name'        => $name,
                    'description' => $definition['description'],
                ])->values()->all(),
                'usage' => 'Call again with one entity to inspect its queryable columns before using query_business_data.',
            ];
        }

        $definition = $this->definition($entity);

        return [
            'entity'           => $entity,
            'description'      => $definition['description'],
            'columns'          => $this->columns($definition['table']),
            'operations'       => self::OPERATIONS,
            'filter_operators' => self::FILTER_OPERATORS,
        ];
    }

    /**
     * @param  array<int, array{column?: mixed, operator?: mixed, value?: mixed}>  $filters
     * @param  array<int, string>  $columns
     */
    public function query(
        string $entity,
        string $operation = 'list',
        ?string $metricColumn = null,
        ?string $groupBy = null,
        array $filters = [],
        array $columns = [],
        ?string $orderBy = null,
        string $direction = 'desc',
        int $limit = 25,
    ): array {
        $definition = $this->definition($entity);
        $table = $definition['table'];
        $availableColumns = $this->columnNames($table);
        $operation = strtolower($operation);

        if (! in_array($operation, self::OPERATIONS, true)) {
            throw new InvalidArgumentException('Unsupported operation.');
        }

        $this->assertColumn($groupBy, $availableColumns);
        $this->assertColumn($orderBy, $availableColumns);

        if (! in_array($direction, ['asc', 'desc'], true)) {
            throw new InvalidArgumentException('Sort direction must be asc or desc.');
        }

        $query = DB::table($table);
        $this->applyCompanyScope($query, $availableColumns);
        $this->applyFilters($query, $filters, $availableColumns);

        if ($operation === 'list') {
            $selectedColumns = $columns === []
                ? array_slice($availableColumns, 0, 12)
                : array_values(array_unique($columns));

            foreach ($selectedColumns as $column) {
                $this->assertColumn($column, $availableColumns, false);
            }

            $sortColumn = $orderBy ?? (in_array('created_at', $availableColumns, true) ? 'created_at' : (in_array('id', $availableColumns, true) ? 'id' : null));

            if ($sortColumn !== null) {
                $query->orderBy($sortColumn, $direction);
            }

            $rows = $query->limit(min(max($limit, 1), 100))->get($selectedColumns);

            return ['entity' => $entity, 'operation' => 'list', 'count' => $rows->count(), 'data' => $rows->all()];
        }

        if ($operation !== 'count') {
            $this->assertColumn($metricColumn, $availableColumns, false);
        }

        if ($groupBy !== null) {
            $expression = $operation === 'count' ? 'COUNT(*)' : strtoupper($operation).'('.DB::getQueryGrammar()->wrap($metricColumn).')';
            $rows = $query->select($groupBy)->selectRaw("{$expression} AS value")
                ->groupBy($groupBy)->orderByDesc('value')->limit(min(max($limit, 1), 100))->get();

            return ['entity' => $entity, 'operation' => $operation, 'group_by' => $groupBy, 'data' => $rows->all()];
        }

        $value = $operation === 'count' ? $query->count() : $query->{$operation}($metricColumn);

        return ['entity' => $entity, 'operation' => $operation, 'column' => $metricColumn, 'value' => $value];
    }

    /** @return array{table: string, description: string} */
    private function definition(string $entity): array
    {
        $definition = BusinessDataCatalog::entities()[strtolower(trim($entity))] ?? null;

        if ($definition === null || ! Schema::hasTable($definition['table'])) {
            throw new InvalidArgumentException("Unknown or unavailable business entity [{$entity}].");
        }

        return $definition;
    }

    /** @return array<int, array{name: string, type: string}> */
    private function columns(string $table): array
    {
        return collect(Schema::getColumns($table))
            ->filter(fn (array $column): bool => ! $this->isSensitive((string) $column['name']))
            ->map(fn (array $column): array => ['name' => (string) $column['name'], 'type' => (string) ($column['type_name'] ?? $column['type'])])
            ->values()->all();
    }

    /** @return array<int, string> */
    private function columnNames(string $table): array
    {
        return array_column($this->columns($table), 'name');
    }

    /** @param array<int, string> $availableColumns */
    private function assertColumn(?string $column, array $availableColumns, bool $nullable = true): void
    {
        if ($nullable && ($column === null || $column === '')) {
            return;
        }

        if ($column === null || ! in_array($column, $availableColumns, true)) {
            throw new InvalidArgumentException("Column [{$column}] is unavailable or protected.");
        }
    }

    /**
     * @param  array<int, array{column?: mixed, operator?: mixed, value?: mixed}>  $filters
     * @param  array<int, string>  $availableColumns
     */
    private function applyFilters(Builder $query, array $filters, array $availableColumns): void
    {
        foreach ($filters as $filter) {
            $column = is_string($filter['column'] ?? null) ? $filter['column'] : null;
            $operator = strtolower((string) ($filter['operator'] ?? '='));
            $value = $filter['value'] ?? null;
            $this->assertColumn($column, $availableColumns, false);

            if (! in_array($operator, self::FILTER_OPERATORS, true)) {
                throw new InvalidArgumentException("Unsupported filter operator [{$operator}].");
            }

            match ($operator) {
                'in', 'not_in'        => $query->{$operator === 'in' ? 'whereIn' : 'whereNotIn'}($column, is_array($value) ? $value : [$value]),
                'between'             => $query->whereBetween($column, is_array($value) && count($value) === 2 ? array_values($value) : throw new InvalidArgumentException('Between requires exactly two values.')),
                'is_null', 'not_null' => $query->{$operator === 'is_null' ? 'whereNull' : 'whereNotNull'}($column),
                'like', 'not_like'    => $query->where($column, $operator === 'like' ? 'like' : 'not like', (string) $value),
                default               => $query->where($column, $operator, $value),
            };
        }
    }

    private function isSensitive(string $column): bool
    {
        return in_array(strtolower($column), BusinessDataCatalog::sensitiveColumns(), true);
    }

    /** @param array<int, string> $availableColumns */
    private function applyCompanyScope(Builder $query, array $availableColumns): void
    {
        if (! in_array('company_id', $availableColumns, true) || auth()->guest()) {
            return;
        }

        $companyIds = function_exists('allowed_company_ids') ? allowed_company_ids() : [];

        if ($companyIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $companyQuery) use ($companyIds): void {
            $companyQuery->whereIn('company_id', $companyIds)->orWhereNull('company_id');
        });
    }
}
