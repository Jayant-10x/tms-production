<?php

namespace App\Http\Controllers;

use App\Enums\DepartmentsEnum;
use App\Enums\DesignationEnum;
use App\Enums\ProjectStatus;
use App\Enums\UserRoleEnum;
use App\Models\Employee;
use App\Models\Project;
use App\Models\SubTaskDoneHistory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AjaxController extends Controller {
    public function getSubDepartments(Request $request, $department) {
        $department = DepartmentsEnum::tryFrom($department);

        if (!$department) {
            return response()->json([
                'data' => [],
                'message' => 'Invalid department specified.',
            ], 404);
        }

        $data = collect(
            DepartmentsEnum::get_sub_departments_by_department($department)
        )->map(fn($department) => [
            'value' => $department->value,
            'label' => $department->label(),
        ])->values();

        return response()->json([
            'data' => $data,
            'message' => 'Sub Departments Found For Department ' . $department->value,
        ]);
    }

    public function getReportingToEmployees(Request $request) {
        $department = $request->department;
        $sub_department = $request->sub_department;
        $emp_id = $request->emp_id;

        $reporting = [];

        if (!empty($department) || !empty($sub_department)) {
            $reportingQuery = Employee::query()->where(function (Builder $query) {
                $query->where('emp_designation', 'manager');
                $query->orWhere('emp_designation', 'tl');
            });

            if (!empty($department)) {
                $reportingQuery->where('emp_department', $department)
                    ->where(function (Builder $query) use ($sub_department) {
                        $query->whereNull('emp_sub_department');
                        if (!empty($sub_department)) {
                            $query->orWhere('emp_sub_department', $sub_department);
                        }
                    });
            }

            if (!empty($emp_id)) {
                $reportingQuery->whereNot('emp_id', $emp_id);
            }
            $reporting = $reportingQuery
                ->get(['emp_id', 'emp_full_name'])
                ->map(function ($employee) {
                    return [
                        'value' => $employee->emp_id,
                        'label' => $employee->emp_full_name,
                    ];
                })
                ->values();
        }
        return response()->json([
            'data' => $reporting,
            'message' => 'Reporting to ' . $department . ' For Department ' . $sub_department,
        ]);
    }

    public function getAddEditPopUpForms(Request $request) {
        $section = my_decrypt($request->section, true);
        $mode = my_decrypt($request->mode, true);
        $primary_id = isset($request->primary_id) ? my_decrypt($request->primary_id) : null;

        $ret_val = [
            'data' => null,
            'secondary_data' => null
        ];
        switch ($section) {
            case 'create-task':
            {
                $ret_val = $this->getCreateTaskFormViaAjax($mode);
                break;
            }
            case 'another_hello':
            {
                $ret_val = $this->getAnotherCreateTaskFormViaAjax($mode, $primary_id);
                break;
            }
        }
        return response()->json([
            'data' => $ret_val['data'],
            'secondary_data' => $ret_val['secondary_data'],
        ]);
    }

    private function getCreateTaskFormViaAjax($mode) {
        if ($mode == 'add') {
            $team_members = get_employee_children_in_depth((int)get_logged_in_user_emp_id(), depth: config('constants.DEFAULT_DEPTH'));
            $projects = [];

            if (get_logged_in_emp_designation() == DesignationEnum::MANAGER || get_logged_in_user_role() == UserRoleEnum::MANAGER->value) {
                $projectBaseQuery = Project::query()->select('pro_id', 'pro_name')->where('pro_status', '=', ProjectStatus::ACTIVE->value);
                $projectBaseQuery->whereIn('pro_manager', array_column($team_members, 'emp_id'))
                    ->orWhere('pro_manager', '=', get_logged_in_user_emp_id());
                $projects = $projectBaseQuery->get()->pluck('pro_name', 'pro_id')->toArray();
            }
            $data = view('project-task.add-project-task', compact('team_members', 'projects'))->render();
        } else {
            $data = '';
        }
        return ['data' => $data, 'secondary_data' => 'Create Task'];
    }

    private function getAnotherCreateTaskFormViaAjax($mode, $primary_id) {
        return ['data' => '<h1>Another Hello Comes from Ajax Controller.</h1>', 'secondary_data' => 'Another Create Task'];
    }

    public function getViewPopUpsPage(Request $request) {
        $section = my_decrypt($request->section, true);
        $mode = my_decrypt($request->mode, true);
        $primary_id = my_decrypt($request->primary_id);

        $ret_val = [
            'data' => null,
            'secondary_data' => null
        ];
        switch ($section) {
            case 'sub-task-history':
            {
                $ret_val = $this->getSubTaskDoneHistoryView($primary_id);
                break;
            }
        }
        return response()->json([
            'data' => $ret_val['data'],
            'secondary_data' => strtoupper($ret_val['secondary_data']),
        ]);
    }

    private function getSubTaskDoneHistoryView($primary_id) {
        if (!empty($primary_id)) {
            $history_data = SubTaskDoneHistory::query()->where('sdh_pst_id', '=', $primary_id)->orderBy('sdh_updated_on', 'desc')->get()->toArray();
            $data = view('project-task.project-task-modals-page._view-sub-task-done-history', compact('history_data'))->render();
        } else {
            $data = '';
        }
        return ['data' => $data, 'secondary_data' => 'Checklist / Subtask done history'];
    }

    public function checkUniqueEmpInternalId(Request $request) {
        $mode = my_decrypt($request->mode, true);
        $isExistQuery = Employee::query()->where('emp_internal_id', '=', $request->internal_id);
        if ($mode == 'edit') {
            $isExistQuery->where('emp_id', '!=', my_decrypt($request->emp_id));
        }
        $isExist = $isExistQuery->exists();
        return response()->json(!$isExist);
    }

    public function checkUniqueEmpEmail(Request $request) {
        $mode = my_decrypt($request->mode, true);
        $isExistQuery = Employee::query()->where('emp_email', '=', $request->email_id);
        if ($mode == 'edit') {
            $isExistQuery->where('emp_id', '!=', my_decrypt($request->emp_id));
        }
        $isExist = $isExistQuery->exists();
        return response()->json(!$isExist);
    }

    public function checkUniqueEmpPhone(Request $request) {
        $mode = my_decrypt($request->mode, true);
        $isExistQuery = Employee::query()->where('emp_phone_number', '=', $request->phone_number);
        if ($mode == 'edit') {
            $isExistQuery->where('emp_id', '!=', my_decrypt($request->emp_id));
        }
        $isExist = $isExistQuery->exists();
        return response()->json(!$isExist);
    }

    private function getTaskViewViaAjax($mode, $primary_id) {
        return ['data' => '<h1>Task View Comes from Ajax Controller.</h1>', 'secondary_data' => 'View Task'];
    }
}
