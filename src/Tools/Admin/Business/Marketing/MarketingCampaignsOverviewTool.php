<?php

namespace Webkul\Mcp\Tools\Admin\Business\Marketing;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class MarketingCampaignsOverviewTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Get marketing campaigns performance and status overview:
        - Total marketing campaigns count
        - Campaigns distribution by advertising platform (Facebook, Google, TikTok, etc.)
        - Campaigns grouped by status (planned, active, completed, paused)
        - Top assigned campaign managers
    MARKDOWN;

    protected function metric(): string
    {
        return 'marketing_campaigns_overview';
    }

    protected function pluginName(): string
    {
        return 'marketing';
    }
}
