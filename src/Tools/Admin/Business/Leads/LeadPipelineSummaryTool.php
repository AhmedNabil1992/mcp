<?php

namespace Webkul\Mcp\Tools\Admin\Business\Leads;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class LeadPipelineSummaryTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Get sales leads and opportunities pipeline summary:
        - Total number of leads
        - Leads grouped by status (new, qualified, lost, won)
        - Leads grouped by source and temperature (cold, warm, hot)
        - Top assigned sales representatives and source campaigns
    MARKDOWN;

    protected function metric(): string
    {
        return 'lead_pipeline_summary';
    }

    protected function pluginName(): string
    {
        return 'leads';
    }
}
