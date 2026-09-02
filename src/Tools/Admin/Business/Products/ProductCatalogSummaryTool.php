<?php

namespace Webkul\Mcp\Tools\Admin\Business\Products;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class ProductCatalogSummaryTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Get product catalog summary:
        - Total products, goods vs services
        - Active product counts
        - Categories distribution
        - Average selling price and average cost price
    MARKDOWN;

    protected function metric(): string
    {
        return 'product_catalog_summary';
    }

    protected function pluginName(): string
    {
        return 'products';
    }
}
