<?php

namespace Webkul\Mcp\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class UniversalQueryService
{
    /**
     * Search ERP records dynamically across allowed entities.
     *
     * @param string $entity (e.g. 'orders', 'invoices', 'products', 'partners', 'employees', 'tickets', 'projects')
     * @param string $query Search keyword
     * @param int $limit Maximum results (default 10)
     * @return array
     */
    public function search(string $entity, string $query, int $limit = 10): array
    {
        $map = [
            'orders'     => ['model' => 'Webkul\\Sale\\Models\\Order', 'fields' => ['name', 'partner_id']],
            'invoices'   => ['model' => 'Webkul\\Account\\Models\\Move', 'fields' => ['name', 'reference']],
            'products'   => ['model' => 'Webkul\\Product\\Models\\Product', 'fields' => ['name', 'sku', 'barcode']],
            'partners'   => ['model' => 'Webkul\\Partner\\Models\\Partner', 'fields' => ['name', 'email', 'phone']],
            'employees'  => ['model' => 'Webkul\\Employee\\Models\\Employee', 'fields' => ['name', 'work_email', 'job_title']],
            'tickets'    => ['model' => 'Webkul\\TechnicalSupport\\Models\\Ticket', 'fields' => ['ticket_number', 'title']],
            'projects'   => ['model' => 'Webkul\\Project\\Models\\Project', 'fields' => ['name', 'description']],
            'leads'      => ['model' => 'Webkul\\Lead\\Models\\Lead', 'fields' => ['name', 'phone', 'email', 'company_name']],
            'campaigns'  => ['model' => 'Webkul\\Marketing\\Models\\Campaign', 'fields' => ['name', 'description']],
            'licenses'   => ['model' => 'Webkul\\Software\\Models\\License', 'fields' => ['serial_number', 'company_name']],
            'instances'  => ['model' => 'Webkul\\SoftwareOnline\\Models\\OnlineInstance', 'fields' => ['instance_number', 'name', 'subdomain']],
            'vouchers'   => ['model' => 'Webkul\\Wifi\\Models\\Voucher', 'fields' => ['code', 'pin']],
        ];

        $entityKey = strtolower(trim($entity));

        if (! isset($map[$entityKey])) {
            return [
                'error' => "Entity [{$entity}] is not supported. Supported entities: " . implode(', ', array_keys($map)),
            ];
        }

        $modelClass = $map[$entityKey]['model'];
        $searchFields = $map[$entityKey]['fields'];

        if (! class_exists($modelClass)) {
            return [
                'error' => "Entity class [{$modelClass}] is not available. Please verify the corresponding plugin is installed.",
            ];
        }

        try {
            $builder = $modelClass::query();

            if (! empty($query)) {
                $builder->where(function ($q) use ($searchFields, $query) {
                    foreach ($searchFields as $index => $field) {
                        if ($index === 0) {
                            $q->where($field, 'like', "%{$query}%");
                        } else {
                            $q->orWhere($field, 'like', "%{$query}%");
                        }
                    }
                });
            }

            $results = $builder->limit(min($limit, 50))->get();

            return [
                'entity' => $entityKey,
                'count'  => $results->count(),
                'data'   => $results->toArray(),
            ];
        } catch (\Throwable $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
}
