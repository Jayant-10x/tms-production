@extends('layouts.vertical', ['title' => 'All Tasks','subTitle' => 'All Tasks'])

@section('content')
    <div class="card">
        {{--<div class="card-header d-flex justify-content-between align-items-center border-bottom">
            <!--Filters-->
        </div>--}}
        <div class="card-body p-0">
            @if(!empty($project_tasks) && count($project_tasks) > 0)
                <div class="table-responsive">
                    <table class="table align-middle text-nowrap table-hover table-centered mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Sr. no.</th>
                            <th width="20%">Task</th>
                            <th width="10%">Project</th>
                            <th width="12%" class="text-center">Assignees</th>
                            <th width="8%">Priority</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($project_tasks as $task)
                            <tr>
                                <td>{{$loop->iteration}}.</td>
                                <td>
                                    {{generate_shorten_string($task->prt_title, 40)}}
                                </td>
                                <td>{{$task->project->pro_name}}</td>
                                <td class="text-center">
                                    @php
                                        $task_assignees = explode(',', $task->assignees);
                                    @endphp
                                    <div class="text-primary fw-bold fs-5">
                                        @for($start = 0; $start < count($task_assignees); $start++)
                                            @if($start > 2)
                                                @continue
                                            @endif
                                            @php
                                                $initial_char_additional_style = '';
                                                if ($start != 0) {
                                                    $initial_char_additional_style = 'style="margin-left: -10px;"';
                                                }
                                            @endphp
                                            <span class="assignee-char" {!! $initial_char_additional_style !!}>
                                                {{ get_initials_char($task_assignees[$start]) }}
                                            </span>
                                        @endfor
                                    </div>
                                    {{--<span data-bs-toggle="tooltip" data-bs-placement="top"
                                          data-bs-title="{{$task->assignees}}"
                                          data-bs-container="body"
                                          class="d-inline-flex align-middle">
                                        <iconify-icon icon="solar:info-circle-bold"
                                                      class="fs-14 text-warning"></iconify-icon>
                                    </span>--}}
                                </td>
                                <td><span
                                        class="badge badge-soft-{{$task->prt_priority?->color()}} badge-outline-{{$task->prt_priority?->color()}} rounded-pill me-1 fs-6"><iconify-icon
                                            icon="solar:flag-2-broken" class="align-middle fs-7"></iconify-icon>{!! $task->prt_priority?->label() !!}</span>
                                </td>
                                <td>{!! get_date_time_format($task->prt_due_date) !!}</td>
                                <td>
                                    <span
                                        class="badge badge-soft-{{$task->prt_status?->color()}} badge-outline-{{$task->prt_status?->color()}} rounded-pill me-1 fs-6">{{$task->prt_status?->label()}}</span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        @if(permission_can('all_tasks', 'view') || is_admin())
                                            {!! generate_view_button(route('task.view', [ 'prt_id' => my_encrypt($task->prt_id), 'called_from'=> 'all_tasks','return_url' => url()->full()])) !!}
                                        @else
                                            -
                                        @endif
                                        {{--{!! generate_edit_button(route('employees.edit', ['emp_id' => my_encrypt($employee->emp_id)])) !!}
                                        {!! generate_delete_button(route('employees.list')) !!}--}}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                {!! generate_no_record_html() !!}
            @endif
            <!-- end table-responsive -->
        </div>
        @if(!empty($project_tasks) && count($project_tasks) > 0)
            <div class="card-footer">
                <div>
                    Showing
                    {{ $project_tasks->firstItem() }}
                    to
                    {{ $project_tasks->lastItem() }}
                    of
                    {{ $project_tasks->total() }}
                    tasks
                </div>

                <div>
                    {{ $project_tasks->links() }}
                </div>
            </div>
        @endif
    </div>
@endsection
