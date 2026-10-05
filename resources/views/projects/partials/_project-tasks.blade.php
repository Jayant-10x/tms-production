@if(!empty($project_tasks) && count($project_tasks) > 0)
    <div class="table-responsive">
        <table class="table align-middle text-nowrap table-hover table-centered mb-0">
            <thead class="table-light">
            <tr>
                <th>Sr. no.</th>
                <th width="15%">Task</th>
                <th width="10%" class="text-center">Assignees</th>
                <th width="12%">Priority</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @foreach($project_tasks as $task)
                <tr>
                    <td>{{$loop->iteration}}.</td>
                    <td>
                        {{generate_shorten_string($task->prt_title)}}
                    </td>
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
                    </td>
                    <td>
                    <span
                        class="badge badge-soft-{{$task->prt_priority?->color()}} badge-outline-{{$task->prt_priority?->color()}} rounded-pill me-1 fs-6"><iconify-icon
                            icon="solar:flag-2-broken" class="align-middle fs-7"></iconify-icon>{!! $task->prt_priority?->label() !!}
                    </span>
                    </td>
                    <td>
                    <span
                        class="badge badge-soft-{{$task->prt_status?->color()}} badge-outline-{{$task->prt_status?->color()}} rounded-pill me-1 fs-6">{{$task->prt_status?->label()}}
                    </span>
                    </td>
                    <td>
                        <div class="d-flex gap-2">
                            @if(permission_can('all_my_tasks', 'view') || is_admin())
                                <div class="d-flex gap-2">
                                    {!! generate_view_button(route('task.view', array_merge(['prt_id' => my_encrypt($task->prt_id),'called_from' => 'projects', 'return_url' => url()->full()],request()->query()))) !!}
                                </div>
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
    {!! generate_pagination($project_tasks,"Tasks") !!}
@else
    <div class="row">
        {!! generate_no_record_html() !!}
    </div>
@endif
