<?php

namespace Webkul\Mcp\Tools\Admin\Business\Partners;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class PartnerInsightsTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Get insights into business partners (customers and suppliers):
        - Total count of partners, customers, and suppliers
        - Distribution of companies vs individuals
        - Top industries and countries represented
    MARKDOWN;

    protected function metric(): string
    {
        return 'partner_insights';
    }

    protected function pluginName(): string
    {
        return 'partners';
    }
}
