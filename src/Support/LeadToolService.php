<?php

namespace Webkul\Mcp\Support;

use Webkul\Lead\Models\Lead;
use Webkul\Mcp\Support\Concerns\HasQueryHelpers;

class LeadToolService
{
    use HasQueryHelpers;

    public function leadPipelineSummary(): array
    {
        $leadModel = Lead::class;

        return [
            'total_leads'       => $this->count($leadModel),
            'leads_by_status'   => $this->groupCount($leadModel, 'status'),
            'leads_by_source'   => $this->groupCount($leadModel, 'source'),
            'leads_by_temp'     => $this->groupCount($leadModel, 'temperature'),
            'top_assigned'      => $this->groupCountLimit($leadModel, 'assigned_to', null, 5),
            'leads_by_campaign' => $this->groupCountLimit($leadModel, 'campaign_id', null, 5),
        ];
    }
}
