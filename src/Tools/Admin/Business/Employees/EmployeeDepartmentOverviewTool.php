<?php

namespace Webkul\Mcp\Tools\Admin\Business\Employees;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class EmployeeDepartmentOverviewTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Get company department metrics and employee allocation per department.
    MARKDOWN;

    protected function metric(): string
    {
        return 'employee_department_overview';
    }

    protected function pluginName(): string
    {
        return 'employees';
    }
}
