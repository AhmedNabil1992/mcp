<?php

namespace Webkul\Mcp\Tools\Admin\Business\Wifi;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class WifiMetricsOverviewTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Get captive portal WiFi network metrics, vouchers, and subscribers overview:
        - Total internet wifi packages
        - Total voucher batches and generated vouchers count
        - Total permanent users and subscribers
        - Packages distribution by type (time, data, bandwidth)
        - Top active voucher batches
    MARKDOWN;

    protected function metric(): string
    {
        return 'wifi_metrics_overview';
    }

    protected function pluginName(): string
    {
        return 'wifi';
    }
}
