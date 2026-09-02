<?php

namespace Webkul\Mcp\Tools\Admin\Business\Employees;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class EmployeeSummaryTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Get overall employee and human resources summary:
        - Total number of employees
        - Department distribution
        - Job titles breakdown
        - Work location distribution
        - Gender and marital status demographics
    MARKDOWN;

    protected function metric(): string
    {
        return 'employee_summary';
    }

    protected function pluginName(): string
    {
        return 'employees';
    }
}
