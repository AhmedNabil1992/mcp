<?php

namespace Webkul\Mcp\Support;

use Webkul\Mcp\Support\Concerns\HasQueryHelpers;
use Webkul\Wifi\Models\PermanentUser;
use Webkul\Wifi\Models\Voucher;
use Webkul\Wifi\Models\WifiPackage;
use Webkul\Wifi\Models\WifiVoucherBatch;

class WifiToolService
{
    use HasQueryHelpers;

    public function wifiMetricsOverview(): array
    {
        $packageModel = WifiPackage::class;
        $batchModel = WifiVoucherBatch::class;
        $voucherModel = Voucher::class;
        $userModel = PermanentUser::class;

        return [
            'total_packages'        => $this->count($packageModel),
            'total_voucher_batches' => $this->count($batchModel),
            'total_vouchers'        => $this->count($voucherModel),
            'total_permanent_users' => $this->count($userModel),
            'packages_by_type'      => $this->groupCount($packageModel, 'package_type'),
            'vouchers_by_batch'     => $this->groupCountLimit($voucherModel, 'batch_id', null, 5),
        ];
    }
}
