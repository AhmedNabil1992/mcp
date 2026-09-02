<?php

namespace Webkul\Mcp\Support;

use Webkul\Mcp\Support\Concerns\HasQueryHelpers;
use Webkul\Recruitment\Models\Applicant;
use Webkul\Recruitment\Models\Candidate;
use Webkul\Recruitment\Models\JobPosition;

class RecruitmentToolService
{
    use HasQueryHelpers;

    public function recruitmentPipeline(): array
    {
        $applicantModel = Applicant::class;
        $jobModel = JobPosition::class;
        $candidateModel = Candidate::class;

        return [
            'total_applicants'       => $this->count($applicantModel),
            'total_candidates'       => $this->count($candidateModel),
            'open_jobs'              => $this->count($jobModel),
            'applicants_by_state'    => $this->groupCount($applicantModel, 'state'),
            'applicants_by_job'      => $this->groupCountLimit($applicantModel, 'job_id', null, 5),
            'applicants_by_stage'    => $this->groupCountLimit($applicantModel, 'stage_id', null, 5),
        ];
    }
}
