<?php

namespace Webkul\Mcp\Support;

use Illuminate\Database\Eloquent\Builder;
use Webkul\Employee\Models\Department;
use Webkul\Employee\Models\Employee;
use Webkul\Mcp\Support\Concerns\HasQueryHelpers;

class EmployeeToolService
{
    use HasQueryHelpers;

    public function employeeSummary(): array
    {
        $model = Employee::class;

        return [
            'total_employees'            => $this->count($model),
            'top_departments_by_count'   => $this->groupCountLimit($model, 'department_id'),
            'top_job_titles'             => $this->groupCountLimit($model, 'job_title'),
            'work_locations'             => $this->groupCountLimit($model, 'work_location_id'),
            'gender_distribution'        => $this->groupCount($model, 'gender'),
            'marital_status'             => $this->groupCount($model, 'marital'),
        ];
    }

    public function employeeDepartmentOverview(): array
    {
        $deptModel = Department::class;
        $empModel = Employee::class;

        $departmentsCount = $this->count($deptModel);
        $employeesByDept = $this->groupCountLimit($empModel, 'department_id', null, 10);

        return [
            'total_departments'        => $departmentsCount,
            'department_distribution'  => $employeesByDept,
        ];
    }
}
