<?php

namespace Webkul\Mcp\Tools\Admin\Business\Recruitments;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class RecruitmentPipelineTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Get recruitment pipeline summary:
        - Total job applicants and candidates
        - Active/open job positions
        - Applicants grouped by state and hiring stage
    MARKDOWN;

    protected function metric(): string
    {
        return 'recruitment_pipeline';
    }

    protected function pluginName(): string
    {
        return 'recruitments';
    }
}
