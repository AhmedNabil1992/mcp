<?php

namespace Webkul\Mcp\Support;

use Webkul\Mcp\Support\Concerns\HasQueryHelpers;
use Webkul\Software\Models\License;
use Webkul\Software\Models\Program;

class SoftwareToolService
{
    use HasQueryHelpers;

    public function softwareLicensesOverview(): array
    {
        $licenseModel = License::class;
        $programModel = Program::class;

        return [
            'total_licenses'       => $this->count($licenseModel),
            'total_programs'       => $this->count($programModel),
            'licenses_by_status'   => $this->groupCount($licenseModel, 'status'),
            'licenses_by_plan'     => $this->groupCount($licenseModel, 'license_plan'),
            'licenses_by_program'  => $this->groupCountLimit($licenseModel, 'program_id', null, 5),
            'top_license_partners' => $this->groupCountLimit($licenseModel, 'partner_id', null, 5),
        ];
    }
}
