@extends('layouts.vertical', ['title' => 'All Projects','subTitle' => 'All Projects'])
@section('content')
    <style>
        .project-first-char-count {
            padding: 10px;
            text-align: center;
            border-radius: 9px;
            font-size: larger;
            font-weight: 700;
            color: white;
        }

        .project-item-card {
            border: 1px solid #d3e8ff;
            border-radius: 10px !important;
        }
    </style>
    @if(!empty($all_projects) && count($all_projects)>0)
        <div class="row">
            @foreach($all_projects as $project)
                @php
                    $total_tasks = count($project->tasks);
                    $completed_tasks = $project->tasks->where('prt_status', \App\Enums\TaskStatus::COMPLETED->value)->count();
                    $task_percentage = $total_tasks > 0 ? ($completed_tasks / $total_tasks) * 100 : 0;

                    $project_task_assignees = $project->tasks->flatMap->projectTaskAssignments->map->projectTaskAssignTo
                    ->filter()->unique('emp_id')->take(3)->pluck('emp_full_name')->values()->toArray();
                @endphp
                <div class="col-xl-4 col-lg-6">
                    @if(permission_can('projects', 'view'))
                        <a href="{{route('project.view', array_merge(['pro_id' => my_encrypt($project->pro_id)],request()->query()))}}"
                           target="_self">
                            @endif
                            <div class="card project-item-card">
                                <div class="card-body">
                                    <div class="row p-2">
                                        <div class="col-md-2 bg-primary project-first-char-count align-content-center">
                                            {{get_initials_char($project->pro_name, 1)}}
                                        </div>
                                        <div class="col-md-7 align-content-center">
                                            <span class="fw-bold">{{$project->pro_name}}</span><br>
                                            <span
                                                class="mt-0 fs-12">{{!empty($project->pro_manager) ? get_employee_data($project->pro_manager)['emp_full_name'] : '-'}}</span>
                                        </div>
                                        <div
                                            class="col-md-3 align-content-center">{!! $project->pro_status->badge() !!}</div>
                                    </div>
                                    @if(!empty($project->pro_description))
                                        <div class="row">
                                            <div
                                                class="col-md-12">{{generate_shorten_string($project->pro_description, 50)}}</div>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="mt-2">
                                            <div class="row justify-content-between mb-1">
                                                <div class="col-md-4 fs-12">{{$completed_tasks}} / {{$total_tasks}}
                                                    Tasks
                                                </div>
                                                <div class="col-md-2 fs-12 text-success fw-bold">{{$task_percentage}}%
                                                </div>
                                            </div>
                                            <div class="progress">
                                                <div
                                                    class="progress-bar bg-primary progress-bar-striped progress-bar-animated"
                                                    role="progressbar" style="width: {{$task_percentage}}%"
                                                    aria-valuenow="{{$task_percentage}}"
                                                    aria-valuemin="0"
                                                    aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row justify-content-around mt-2">
                                        <div class="col-md-7">
                                            <div class="text-primary fw-bold fs-5">
                                                @for($start = 0; $start < count($project_task_assignees); $start++)
                                                    @if($start > 5)
                                                        @continue
                                                    @endif
                                                    @php
                                                        $initial_char_additional_style = '';
                                                        if ($start != 0) {
                                                            $initial_char_additional_style = 'style="margin-left: -10px;"';
                                                        }
                                                    @endphp
                                                    <span class="assignee-char" {!! $initial_char_additional_style !!}>
                                                        {{ get_initials_char($project_task_assignees[$start]) }}
                                                    </span>
                                                @endfor
                                            </div>
                                        </div>
                                        <div class="col-md-5 fs-12 text-end">
                                            <iconify-icon icon="solar:calendar-bold"
                                                          class="align-middle"></iconify-icon> {{get_date_time_format($project->pro_deadline)}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @if(permission_can('projects', 'view'))
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
        {!! generate_pagination($all_projects,"Projects") !!}
    @else
        <div class="card">
            <div class="card-body p-0">
                {!! generate_no_record_html() !!}
            </div>
        </div>
    @endif
@endsection

@if(/*!is_admin() && get_logged_in_user_role() == 'manager' && */!is_admin() && permission_can('projects', 'add'))
    @section('module-right-section')
        {!! generate_add_button(route('projects.add',request()->query()), title:' Project', text: ' Project') !!}
    @endsection
@endif
