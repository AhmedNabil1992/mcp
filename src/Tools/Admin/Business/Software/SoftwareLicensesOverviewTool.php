<?php

namespace Webkul\Mcp\Tools\Admin\Business\Software;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class SoftwareLicensesOverviewTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Get offline software desktop licenses and activations overview:
        - Total software licenses and desktop programs count
        - Licenses distribution by status (active, expired, suspended)
        - Licenses distribution by license plan
        - Top programs and license holders / partners
    MARKDOWN;

    protected function metric(): string
    {
        return 'software_licenses_overview';
    }

    protected function pluginName(): string
    {
        return 'software';
    }
}
