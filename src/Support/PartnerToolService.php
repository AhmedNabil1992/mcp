<?php

namespace Webkul\Mcp\Support;

use Webkul\Mcp\Support\Concerns\HasQueryHelpers;
use Webkul\Partner\Models\BankAccount;
use Webkul\Partner\Models\Industry;
use Webkul\Partner\Models\Partner;

class PartnerToolService
{
    use HasQueryHelpers;

    public function partnerInsights(): array
    {
        $partnerModel = Partner::class;

        return [
            'total_partners'           => $this->count($partnerModel),
            'customers_count'          => $this->count($partnerModel, fn ($q) => $q->where('customer_rank', '>', 0)),
            'suppliers_count'          => $this->count($partnerModel, fn ($q) => $q->where('supplier_rank', '>', 0)),
            'individuals_count'        => $this->count($partnerModel, fn ($q) => $q->where('is_company', false)),
            'companies_count'          => $this->count($partnerModel, fn ($q) => $q->where('is_company', true)),
            'partners_by_industry'     => $this->groupCountLimit($partnerModel, 'industry_id', null, 5),
            'partners_by_country'      => $this->groupCountLimit($partnerModel, 'country_id', null, 5),
        ];
    }
}
