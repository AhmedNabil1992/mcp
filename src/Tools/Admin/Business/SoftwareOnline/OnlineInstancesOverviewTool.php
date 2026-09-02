<?php

namespace Webkul\Mcp\Tools\Admin\Business\SoftwareOnline;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class OnlineInstancesOverviewTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Get cloud SaaS online instances and subscriptions overview:
        - Total cloud online instances and systems count
        - Instances distribution by status (active, pending, expired, suspended)
        - Instances distribution by billing cycle (monthly, annually)
        - Top cloud systems and subscribers / partners
    MARKDOWN;

    protected function metric(): string
    {
        return 'online_instances_overview';
    }

    protected function pluginName(): string
    {
        return 'software-online';
    }
}
