<form
    action="{{!empty($admin_data) ? route('admin_user.update', array_merge(['adm_id' => my_encrypt($admin_data->adm_id)],request()->query())) : route('admin_user.save', array_merge(['emp_id' => my_encrypt($emp_data->emp_id)],request()->query()))}}"
    method="post" id="credential_form">
    @csrf
    @if(!empty($admin_data))
        @method('PUT')
    @endif
    <div class="row">
        <div class="col-lg-4">
            <div class="mb-3">
                <label for="user_name" class="form-label">User Name <span class="text-danger">*</span></label>
                <input type="text" id="user_name" name="user_name"
                       class="form-control @error('user_name') is-invalid @enderror"
                       autocomplete="new-username"
                       value="{{$admin_data->adm_user_name ?? old('user_name')}}">
                @error('user_name')
                <span class="validation-message">{{ $message }}</span>
                @enderror
            </div>
        </div>
        <div class="col-lg-4">
            <div class="mb-3">
                <label for="role" class="form-label">Role <span
                        class="text-danger">*</span></label>
                <select class="form-control @error('role') is-invalid @enderror"
                        id="role" data-choices data-choices-sorting-false
                        data-placeholder="Select Role" name="role">
                    <option value="">Select Role</option>
                    @foreach(config('constants.ROLES') as $user_role_key => $user_role_val)
                        @if($user_role_val == config('constants.ROLES.admin'))
                            @continue
                        @endif
                        <option value="{{$user_role_key}}"
                            @selected(old('role', $admin_data->adm_role ?? null) === $user_role_key)>
                            {{$user_role_val}}
                        </option>
                    @endforeach
                </select>
                @error('role')
                <span class="validation-message">{{ $message }}</span>
                @enderror
            </div>
        </div>
        <div class="col-lg-4">
            <div class="mb-3">
                <label for="password" class="form-label">Password @empty($admin_data)
                        <span
                            class="text-danger">*</span>
                    @endempty</label>
                <input type="password" id="password" name="password"
                       class="form-control @error('password') is-invalid @enderror"
                       autocomplete="new-password">
                @error('password')
                <span class="validation-message">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>
    {!! generate_submit_reset_button() !!}
</form>

<script>
    let hasPassword = '{{isset($admin_data) && !empty($admin_data->adm_password) ? true: false}}';
</script>
