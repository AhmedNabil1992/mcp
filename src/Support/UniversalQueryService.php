<?php

namespace Webkul\Mcp\Support;

use Illuminate\Support\Facades\Schema;

class UniversalQueryService
{
    /**
     * Search ERP records dynamically across allowed entities.
     *
     * @param  string  $entity  (e.g. 'orders', 'invoices', 'products', 'partners', 'employees', 'tickets', 'projects')
     * @param  string  $query  Search keyword
     * @param  int  $limit  Maximum results (default 10)
     */
    public function search(string $entity, string $query, int $limit = 10): array
    {
        $map = [
            'orders' => ['model' => 'Webkul\\Sale\\Models\\Order', 'fields' => ['name', 'partner_id']],
            'invoices' => ['model' => 'Webkul\\Account\\Models\\Move', 'fields' => ['name', 'reference']],
            'products' => ['model' => 'Webkul\\Product\\Models\\Product', 'fields' => ['name', 'sku', 'barcode']],
            'partners' => ['model' => 'Webkul\\Partner\\Models\\Partner', 'fields' => ['name', 'email', 'phone']],
            'employees' => ['model' => 'Webkul\\Employee\\Models\\Employee', 'fields' => ['name', 'work_email', 'job_title']],
            'tickets' => ['model' => 'Webkul\\TechnicalSupport\\Models\\Ticket', 'fields' => ['ticket_number', 'title']],
            'projects' => ['model' => 'Webkul\\Project\\Models\\Project', 'fields' => ['name', 'description']],
            'leads' => ['model' => 'Webkul\\Lead\\Models\\Lead', 'fields' => ['name', 'phone', 'email', 'company_name']],
            'campaigns' => ['model' => 'Webkul\\Marketing\\Models\\Campaign', 'fields' => ['name', 'description']],
            'licenses' => ['model' => 'Webkul\\Software\\Models\\License', 'fields' => ['serial_number', 'company_name']],
            'instances' => ['model' => 'Webkul\\SoftwareOnline\\Models\\OnlineInstance', 'fields' => ['instance_number', 'name', 'subdomain']],
            'vouchers' => ['model' => 'Webkul\\Wifi\\Models\\Voucher', 'fields' => ['code', 'pin']],
            'activities' => ['model' => 'Webkul\\Chatter\\Models\\Message', 'fields' => ['subject', 'summary']],
            'expenses' => ['model' => 'Webkul\\Expense\\Models\\Expense', 'fields' => ['reference', 'description']],
            'manufacturing_orders' => ['model' => 'Webkul\\Manufacturing\\Models\\Order', 'fields' => ['reference']],
            'maintenance_requests' => ['model' => 'Webkul\\Maintenance\\Models\\MaintenanceRequest', 'fields' => ['name', 'description']],
            'articles' => ['model' => 'Webkul\\Article\\Models\\Article', 'fields' => ['title', 'summary']],
            'blog_posts' => ['model' => 'Webkul\\Blog\\Models\\Post', 'fields' => ['title', 'sub_title']],
            'referral_codes' => ['model' => 'Webkul\\Referral\\Models\\ReferralCode', 'fields' => ['code']],
            'vpn_servers' => ['model' => 'Webkul\\Vpn\\Models\\VpnServer', 'fields' => ['name', 'host']],
        ];

        $entityKey = strtolower(trim($entity));

        if (! isset($map[$entityKey])) {
            return [
                'error' => "Entity [{$entity}] is not supported. Supported entities: ".implode(', ', array_keys($map)),
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
            $model = new $modelClass;
            $columns = Schema::getColumnListing($model->getTable());
            $searchFields = array_values(array_intersect($searchFields, $columns));
            $visibleColumns = array_values(array_unique(array_intersect(
                ['id', ...$searchFields, 'state', 'status', 'created_at', 'updated_at'],
                $columns,
            )));
            $builder = $modelClass::query()->select($visibleColumns);

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

            $results = $builder->limit(min(max($limit, 1), 50))->get();

            return [
                'entity' => $entityKey,
                'count' => $results->count(),
                'data' => $results->toArray(),
            ];
        } catch (\Throwable $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
}
