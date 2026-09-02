<?php

namespace Webkul\Mcp\Support;

use Webkul\Marketing\Models\Campaign;
use Webkul\Mcp\Support\Concerns\HasQueryHelpers;

class MarketingToolService
{
    use HasQueryHelpers;

    public function marketingCampaignsOverview(): array
    {
        $campaignModel = Campaign::class;

        return [
            'total_campaigns'        => $this->count($campaignModel),
            'campaigns_by_platform'  => $this->groupCount($campaignModel, 'platform'),
            'campaigns_by_status'    => $this->groupCount($campaignModel, 'status'),
            'campaigns_by_assignee'  => $this->groupCountLimit($campaignModel, 'assigned_to', null, 5),
        ];
    }
}
