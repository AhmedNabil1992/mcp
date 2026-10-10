<?php

namespace Webkul\Mcp\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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

        $today = Carbon::today();
        $start = $today->copy()->startOfMonth()->toDateString();
        $end = $today->copy()->endOfMonth()->toDateString();
        $createdThisMonth = fn (Builder $query) => $query->whereBetween('created_at', [$start.' 00:00:00', $end.' 23:59:59']);
        $activatedThisMonth = fn (Builder $query) => $query
            ->whereBetween('start_date', [$start, $end])
            ->where('status', 'approved')
            ->where('is_active', true);

        return [
            'current_month' => [
                'from' => $start,
                'to' => $end,
                'created_count' => $this->count($licenseModel, $createdThisMonth),
                'activated_count' => $this->count($licenseModel, $activatedThisMonth),
                'activated_by_plan' => $this->groupCount($licenseModel, 'license_plan', $activatedThisMonth),
                'activated_by_program' => $this->groupCountLimit($licenseModel, 'program_id', $activatedThisMonth, 10),
                'created_by_status' => $this->groupCount($licenseModel, 'status', $createdThisMonth),
                'created_by_plan' => $this->groupCount($licenseModel, 'license_plan', $createdThisMonth),
                'created_by_program' => $this->groupCountLimit($licenseModel, 'program_id', $createdThisMonth, 10),
                'requested_count' => $this->count($licenseModel, fn (Builder $query) => $query->whereBetween('requested_at', [$start.' 00:00:00', $end.' 23:59:59'])),
                'started_count' => $this->count($licenseModel, fn (Builder $query) => $query->whereBetween('start_date', [$start, $end])),
                'definition' => 'activated_count is the approved active licenses whose license start date is in the month. created_count is only database record creation and may reflect imports.',
            ],
            'total_licenses'       => $this->count($licenseModel),
            'total_programs'       => $this->count($programModel),
            'licenses_by_status'   => $this->groupCount($licenseModel, 'status'),
            'licenses_by_plan'     => $this->groupCount($licenseModel, 'license_plan'),
            'licenses_by_program'  => $this->groupCountLimit($licenseModel, 'program_id', null, 5),
            'top_license_partners' => $this->groupCountLimit($licenseModel, 'partner_id', null, 5),
        ];
    }
}
