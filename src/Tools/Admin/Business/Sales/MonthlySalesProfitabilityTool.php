<?php

namespace Webkul\Mcp\Tools\Admin\Business\Sales;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class MonthlySalesProfitabilityTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Get this month's complete posted invoiced sales and estimated profitability. This includes both invoices created from sales orders and direct customer invoices, subtracts customer credit notes, calculates product/BOM component cost, and treats service products as 100% gross profit. Use this—not sales_order_insights—when the user asks for monthly sales revenue, invoices, gross profit, profitability, margin, or direct invoices.
    MARKDOWN;

    protected function metric(): string
    {
        return 'monthly_sales_profitability';
    }

    protected function pluginName(): string
    {
        return 'invoices';
    }
}
