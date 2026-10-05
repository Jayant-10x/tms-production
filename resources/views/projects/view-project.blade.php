@extends('layouts.vertical', ['title' => 'View Project','subTitle' => 'View Project'])
@push('css')
    @vite(['resources/css/project.css','resources/css/choices.css'])
@endpush
@section('content')
    @php
        $all_permission = (get_logged_in_user_role() == \App\Enums\UserRoleEnum::MANAGER->value && permission_can('all_tasks', 'edit')) || is_admin();
        $employee_permission = get_logged_in_user_role() == \App\Enums\UserRoleEnum::EMPLOYEE->value && permission_can('all_my_tasks', 'edit');
    @endphp
    <div>
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="row">
                    <div class="col-md-1">
                        <div class="bg-primary project-first-char-count align-content-center">
                            {{get_initials_char($project_data['pro_name'], 1)}}
                        </div>
                    </div>
                    <div class="col-md-11 align-content-center">
                        <span class="fw-bold fs-4">{{$project_data['pro_name']}}</span><br>
                        @if(!empty($project_data['pro_description']))
                            <span
                                class="text-break">{{generate_shorten_string($project_data['pro_description'], 140)}}</span>
                        @endif
                    </div>
                </div>
            </div>
            @if($all_permission)
                <div class="col-md-4">
                    <div class="row justify-content-end">
                        <div class="col-md-8">
                            <select class="form-control rounded-4" name="project_status" id="project_status_toggle"
                                    data-choices data-choices-sorting-false>
                                <option value="">select status</option>
                                <optgroup label="">
                                    @foreach(\App\Enums\ProjectStatus::cases() as $status)
                                        <option
                                            value="{{ $status->value }}"
                                            @selected(old('project_status', $project_data['pro_status']?->value ?? $project_data['pro_status'] ?? null) === $status->value)>
                                            {{ $status->label()}}
                                        </option>
                                    @endforeach
                                </optgroup>
                            </select>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="mini-card card-purple">

                    <div class="mini-icon">
                        <i class="bi bi-list-task"></i>
                    </div>

                    <div class="mini-number">
                        {{count($project_tasks) ?? 0}}
                    </div>

                    <div class="mini-label">
                        Total Tasks
                    </div>

                </div>


                {{--<div class="top-card card">
                    <div class="card-body">
                        <div class="fs-3 fw-bold text-primary">24</div>
                        <div class="fs-8">Total Tasks</div>
                    </div>
                </div>--}}
            </div>
            <div class="col-md-3">
                <div class="mini-card card-green">
                    <div class="mini-icon">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div class="mini-number">
                        {{$task_statistics['completed_tasks']}}
                    </div>
                    <div class="mini-label">
                        Completed
                    </div>
                </div>

                {{--<div class="top-card card">
                    <div class="card-body">
                        <div class="fs-3 fw-bold text-success">18</div>
                        <div class="fs-8">Completed</div>
                    </div>
                </div>--}}
            </div>
            <div class="col-md-3">
                <div class="mini-card card-yellow">
                    <div class="mini-icon">
                        <i class="bi bi-fire"></i>
                    </div>
                    <div class="mini-number">
                        {{$task_statistics['pending_tasks']}}
                    </div>
                    <div class="mini-label">
                        Remaining
                    </div>

                </div>

                {{--<div class="top-card card">
                    <div class="card-body">
                        <div class="fs-3 fw-bold text-warning">6</div>
                        <div class="fs-8">Remaining</div>
                    </div>
                </div>--}}
            </div>
            <div class="col-md-3">
                <div class="mini-card card-pink">
                    <div class="mini-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="mini-number">
                        {{count($project_team) ?? 0}}
                    </div>
                    <div class="mini-label">
                        Team Size
                    </div>

                </div>

                {{--<div class="top-card card">
                    <div class="card-body">
                        <div class="fs-3 fw-bold text-info">4</div>
                        <div class="fs-8">Team Size</div>
                    </div>
                </div>--}}
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <ul class="nav nav-tabs">
                            <li class="nav-item">
                                <a href="#project_tasks" data-bs-toggle="tab" aria-expanded="false"
                                   class="nav-link active">
                                    <span class="d-block d-sm-none"><i class="bi bi-list-check"></i></span>
                                    <span class="d-none d-sm-block">Tasks ({{count($project_tasks)}})</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#project_team" data-bs-toggle="tab" aria-expanded="true" class="nav-link">
                                    <span class="d-block d-sm-none"><i class="bi bi-people"></i></span>
                                    <span class="d-none d-sm-block">Team</span>
                                </a>
                            </li>
                            {{--<li class="nav-item">
                                <a href="#project_activity" data-bs-toggle="tab" aria-expanded="true" class="nav-link">
                                    <span class="d-block d-sm-none"><i class="bx bx-user"></i></span>
                                    <span class="d-none d-sm-block">Activity</span>
                                </a>
                            </li>--}}
                        </ul>
                        <div class="tab-content text-muted">
                            <div class="tab-pane show active" id="project_tasks">
                                @include('projects.partials._project-tasks')
                            </div>
                            <div class="tab-pane" id="project_team">
                                @include('projects.partials._project-team', [$project_data])
                            </div>
                            {{--<div class="tab-pane" id="project_activity">
                                @include('projects.partials._project-activity')
                            </div>--}}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="progress-card card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <span class="fs-5 fw-medium text-dark">PROGRESS</span>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <div class="row justify-content-between mb-1">
                                    <div class="col-md-4 fs-12 text-dark"><span id="task-completed"></span> / <span
                                            id="task-total"></span> Tasks
                                    </div>
                                    <div class="col-md-2 fs-12 text-primary fw-bold" id="task-percentage"></div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="progress">
                                            <div
                                                id="task-progress-bar"
                                                class="progress-bar bg-primary progress-bar-striped progress-bar-animated"
                                                role="progressbar" style="width: 0%" aria-valuenow="0"
                                                aria-valuemin="0"
                                                aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <span class="fs-5 fw-medium text-dark">DEADLINE</span>
                            </div>
                            <div class="col-md-12">
                                <span class="mt-2">
                                    <iconify-icon icon="solar:calendar-broken"></iconify-icon>
                                </span>
                                <span
                                    class="fs-6 fw-medium">{{get_date_time_format($project_data['pro_deadline'])}}</span>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <span class="fs-5 fw-medium text-dark">PROJECT MANAGER</span>
                            </div>
                            <div class="col-md-12 align-content-center">
                                <span class="mt-2">
                                    <iconify-icon icon="solar:user-broken"></iconify-icon>
                                </span>
                                <span
                                    class="fs-6 fw-medium">{{!empty($project_data['pro_manager']) ? get_employee_data($project_data['pro_manager'])['emp_full_name'] : '-'}}</span>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <span class="fs-5 fw-medium text-dark">STATUS</span>
                            </div>
                            <div class="col-md-12 align-content-center">
                                {!! \App\Enums\ProjectStatus::tryFrom($project_data['pro_status'])->badge() !!}
                            </div>
                        </div>
                    </div>
                </div>

                @if(!empty($project_manager))
                    @php
                        $emp_photo = asset('images/users/dummy-avatar.jpg');
                        if(!empty($project_manager['emp_photo'])) {
                            $emp_photo = asset('storage/employees/'. $project_manager['emp_photo']);
                        }
                    @endphp
                    @if(permission_can('team_members', 'view'))
                        <a href="{{route('team_member.view', array_merge(['called_from' => 'project_team', 'member_id' => my_encrypt($project_data['pro_manager']), 'pro_id' => my_encrypt($pro_id)],request()->query()))}}"
                           target="_self">
                            @endif
                            <div class="card team-members-card">
                                <div class="card-body">
                        <span class="team-member-role-pill">
                            <span class="badge rounded-pill badge-outline-purple me-1 fs-6 badge-soft-purple">Project Manager</span>
                            {{--{!! \App\Enums\UserRoleEnum::tryFrom('manager')->badge() !!}--}}
                        </span>
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <img src="{{$emp_photo}}"
                                             class="avatar-lg rounded-3 border border-light border-3 object-fit-cover">
                                        <div>
                                            <p class="text-dark fw-medium fs-16 mb-0">{{$project_manager['emp_full_name']}}</p>
                                            <p class="mb-0 badge designation-badge rounded-pill me-1 fs-6">{{$project_manager['emp_designation']->label()}}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @if(permission_can('team_members', 'view'))
                        </a>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endsection
@section('module-right-section')
    {!! generate_back_to_list_button(route('projects.list',request()->query())) !!}
@endsection

@push('script')
    <script>
        let called_from = 'view_project';
        let total = {{count($project_tasks) ?? 0}};
        let completed = {{$task_statistics['completed_tasks']}};
        let pro_id = '{{my_encrypt($pro_id)}}';
    </script>
    @vite(['resources/js/pages/projects.js' ])
@endpush
