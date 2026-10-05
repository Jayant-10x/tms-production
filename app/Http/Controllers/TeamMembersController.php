<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Models\Employee;
use App\Models\ProjectTask;

class TeamMembersController extends Controller {
    public function index() {
        if (is_admin()) {
            $all_team_members = Employee::query()->with('admin_user_details')->select('emp_id', 'emp_internal_id', 'emp_full_name', 'emp_email', 'emp_designation', 'emp_department', 'emp_sub_department', 'emp_joining_date', 'emp_status', 'emp_photo')->where('emp_status', '=', 1)->paginate(config('constants.PER_PAGE_ITEM_COUNT'), pageName: 'team_members')->withQueryString();
        } else {
            $employees = get_employee_children_in_depth((int)get_logged_in_user_emp_id(), depth: 3);
            $employee_ids = array_column($employees, 'emp_id');
            $employee_ids[] = get_logged_in_user_emp_id();

            $all_team_members = Employee::query()->with('admin_user_details')->select('emp_id', 'emp_internal_id', 'emp_full_name', 'emp_email', 'emp_designation', 'emp_department', 'emp_sub_department', 'emp_joining_date', 'emp_status', 'emp_photo')
                ->where('emp_status', '=', 1)
                ->whereIn('emp_id', $employee_ids)
                ->paginate(config('constants.PER_PAGE_ITEM_COUNT'), pageName: 'team_members')
                ->withQueryString();
        }
        $member_ids = array_column($all_team_members->toArray()['data'], 'emp_id');
        $task_statistics = (new ProjectTaskController())->userTaskStatistics($member_ids);
        return view('team-members.all-team-members', compact('all_team_members', 'task_statistics'));
    }

    public function viewTeamMember($called_from, $member_id, $pro_id = null) {
        $member_id = my_decrypt($member_id);
        $member_info = Employee::query()->with('admin_user_details')->select('emp_id', 'emp_internal_id', 'emp_full_name', 'emp_email', 'emp_designation', 'emp_photo', 'emp_department', 'emp_sub_department')->where('emp_id', '=', $member_id)/* ->groupBy('emp_id') */ ->first()?->toArray();

        if (empty($member_info)) {
            return redirect()->back()->with('error', 'Team Member Not Found' . ($called_from == 'project_team' ? ' In Project' : ''));
        }

        $emp_tasks = ProjectTask::query()->select('prt_id', 'prt_title', 'pro_name', 'prt_priority', 'prt_status', 'prt_due_date')->join('project_task_assignments', 'prt_id', '=', 'pta_prt_id')->join('projects', 'prt_pro_id', '=', 'pro_id')->where('pta_assign_to', '=', $member_id)->paginate(config('constants.PER_PAGE_ITEM_COUNT'), pageName: 'tasks')->withQueryString();

        $emp_task_statistics = [
            'total_task' => count($emp_tasks),
            'completed_task' => $emp_tasks->where('prt_status', '=', ProjectStatus::COMPLETED->value)->count(),
        ];

        return view('team-members.view-team-member', compact('member_info', 'called_from', 'pro_id', 'emp_tasks', 'emp_task_statistics'));
    }
}
