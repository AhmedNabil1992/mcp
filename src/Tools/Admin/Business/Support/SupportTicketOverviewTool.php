<?php

namespace Webkul\Mcp\Tools\Admin\Business\Support;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class SupportTicketOverviewTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Get technical support ticket overview:
        - Total support tickets
        - Unread and reopened ticket counts
        - Tickets distribution by status and priority
        - Top assigned support agents and frequent partners
    MARKDOWN;

    protected function metric(): string
    {
        return 'support_ticket_overview';
    }

    protected function pluginName(): string
    {
        return 'technical-support';
    }
}
