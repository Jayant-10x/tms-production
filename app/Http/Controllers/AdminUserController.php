<?php

namespace App\Http\Controllers;

use App\Enums\UserRoleEnum;
use App\Models\AdminUser;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminUserController extends Controller {

    public function allAdminUsers(Request $request) {
        $filter_arr = [];

        $adminBaseQuery = AdminUser::query();
        if ($request->filled('adm_name')) {
            $adminBaseQuery->where('adm_name', 'like', '%' . $request->adm_name . '%');
            $filter_arr = ['adm_name' => $request->adm_name];
        }

        if ($request->filled('adm_username')) {
            $adminBaseQuery->where('adm_user_name', 'like', '%' . $request->adm_username . '%');
            $filter_arr = ['adm_username' => $request->adm_username];
        }

        if ($request->filled('adm_status')) {
            $adminBaseQuery->where('adm_status', '=', $request->adm_status);
            $filter_arr = ['adm_status' => $request->adm_status];
        }

        $all_users = $adminBaseQuery->where('adm_role', '=', UserRoleEnum::ADMIN->value)->paginate(config('constants.PER_PAGE_ITEM_COUNT'), pageName: 'admin_users')->withQueryString();
        return view('admin-user.all-admin-user', compact('all_users', 'filter_arr'));
    }

    public function store(Request $request, $emp_id) {
        $emp_id = my_decrypt($emp_id);
        $request->validate([
            'user_name' => 'required|min:6|max:' . MAX_LENGTH_20,
            'password' => 'required_with:user_name|min:8|max:' . MAX_LENGTH_20,
            'role' => 'required',
        ]);

        $current_date = date(config('constants.DB_DATE_TIME_FORMAT'));
        $emp_data = Employee::query()->where('emp_id', '=', $emp_id)->first();

        $admin_user = new AdminUser();
        $admin_user->adm_emp_id = $emp_id;
        $admin_user->adm_name = $emp_data->emp_full_name;
        $admin_user->adm_user_name = $request->user_name;
        $admin_user->adm_role = $request->role;
        $admin_user->adm_password = $request->password;
        $admin_user->adm_created_by = setCreatedUpdatedBy();
        $admin_user->adm_created_on = $current_date;
        if ($admin_user->save()) {
            return redirect()->back()->with('success', 'Credentials added successfully.');
        } else {
            return redirect()->back()->with('error', 'Something went wrong.');
        }
    }

    public function update(Request $request, $adm_id) {
        $request->validate([
            'user_name' => 'required|min:6|max:' . MAX_LENGTH_20,
            'password' => 'nullable|min:8|max:' . MAX_LENGTH_20,
            'role' => 'required',
        ]);

        $current_date = date(config('constants.DB_DATE_TIME_FORMAT'));

        $admin_user_exist = AdminUser::query()->where('adm_id', my_decrypt($adm_id))->first();
        $admin_user_exist->adm_user_name = $request->user_name;
        $admin_user_exist->adm_role = $request->role;
        $admin_user_exist->adm_password = $request->password;
        $admin_user_exist->adm_updated_by = setCreatedUpdatedBy();
        $admin_user_exist->adm_updated_on = $current_date;
        if ($admin_user_exist->save()) {
            return redirect()->back()->with('success', 'Credentials updated successfully.');
        } else {
            return redirect()->back()->with('error', 'Something went wrong.');
        }
    }

    public function addAdmin() {
        $mode = 'add';
        $called_from = 'admin';
        return view('admin-user.add-edit-admin-user', compact('mode', 'called_from'));
    }

    // save admin details from the admin modules
    public function saveAdmin(Request $request) {
        $request->validate([
            'admin_name' => 'required|min:' . MIN_LENGTH . '|max:' . MAX_LENGTH_100,
            'user_name' => 'required|min:6|max:' . MAX_LENGTH_20,
            'password' => 'required|min:8|max:' . MAX_LENGTH_20,
            'status' => 'required',
            'admin_photo' => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
        ]);

        $current_date = date(config('constants.DB_DATE_TIME_FORMAT'));

        $admin = new AdminUser();
        $admin->adm_name = $request->admin_name;
        $admin->adm_user_name = $request->user_name;
        $admin->adm_status = $request->status;
        $admin->adm_role = 'admin';
        $admin->adm_password = $request->password;
        if ($request->hasFile('admin_photo')) {
            $file = $request->file('admin_photo');

            // Slugify the original name and append a unique identifier
            $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                . '_' . uniqid()
                . '.' . $file->getClientOriginalExtension();
            $admin->adm_photo = $filename;
            $file->storeAs('admin', $filename, 'public'); // it returns the image path
        }
        $admin->adm_created_by = setCreatedUpdatedBy();
        $admin->adm_created_on = $current_date;
        $is_saved = $admin->save();

        if ($is_saved) {
            return redirect(route('admin_user_module.list', $request->query()))->with('success', 'Admin User added successfully.');
        } else {
            return redirect(route('admin_user_module.list', $request->query()))->with('error', 'Something went wrong.');
        }
    }

    public function editAdmin($adm_id) {
        $mode = 'edit';
        $called_from = 'admin';
        $adm_id = my_decrypt($adm_id);
        $admin_data = AdminUser::query()->where('adm_id', $adm_id)->first();

        if (empty($admin_data)) {
            return redirect()->back()->with('error', 'Admin User not found.');
        }
        return view('admin-user.add-edit-admin-user', compact('mode', 'admin_data', 'called_from'));
    }

    public function updateAdmin(Request $request, $adm_id) {
        $adm_id = my_decrypt($adm_id);
        $request->validate([
            'admin_name' => 'required|min:' . MIN_LENGTH . '|max:' . MAX_LENGTH_100,
            'user_name' => 'required|min:6|max:' . MAX_LENGTH_20,
            'password' => 'nullable|min:8|max:' . MAX_LENGTH_20,
            'status' => $adm_id != get_logged_in_user_id() ? 'required' : 'nullable',
            'admin_photo' => 'nullable|file|mimes:jpeg,jpg,png|max:5120',
        ]);

        $current_date = date(config('constants.DB_DATE_TIME_FORMAT'));

        $admin_exist = AdminUser::query()->where('adm_id', $adm_id)->first();
        if ($admin_exist) {
            $admin_exist->adm_name = $request->admin_name;
            $admin_exist->adm_user_name = $request->user_name;
            if ($adm_id != get_logged_in_user_id()) {
                $admin_exist->adm_status = $request->status;
            }

            if (!empty($request->password)) {
                $admin_exist->adm_password = $request->password;
            }

            if ($request->hasFile('admin_photo')) {
                // Delete old photo
                if (!empty($admin_exist->adm_photo)) {
                    Storage::disk('public')->delete(
                        'admin/' . $admin_exist->adm_photo
                    );
                }
                $file = $request->file('admin_photo');

                $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $admin_exist->adm_photo = $filename;
                $file->storeAs('admin', $filename, 'public');
            } else {
                if (!empty($admin_exist->adm_photo)) {
                    Storage::disk('public')->delete(
                        'admin/' . $admin_exist->adm_photo
                    );
                }
                $admin_exist->adm_photo = null;
            }

            $admin_exist->adm_updated_by = setCreatedUpdatedBy();
            $admin_exist->adm_updated_on = $current_date;
            $is_updated = $admin_exist->save();

            /*$notificationService = new NotificationService();
            $notificationService->sendToCurrentUser(
                title: 'Admin Updated',
                message: "Admin was updated successfully.",
                route: 'admin_user_module.list',
                params: ['admin' => get_logged_in_user_id(),]);*/

            if ($is_updated) {
                Auth::setUser($admin_exist->fresh());
//                dd(Auth::user());
                return redirect()->route('admin_user_module.edit', array_merge(['adm_id' => my_encrypt($adm_id)], $request->query()))->with('success', 'Admin User updated successfully.');
            } else {
                return redirect()->route('admin_user_module.edit', array_merge(['adm_id' => my_encrypt($adm_id)], $request->query()))->with('error', 'Something went wrong.');
            }
        } else {
            return redirect()->route('admin_user_module.edit', array_merge(['adm_id' => my_encrypt($adm_id)], $request->query()))->with('error', 'Admin User not found.');
        }
    }

    public function viewAdmin($adm_id) {
        $called_from = 'admin';
        $created_updated_arr = ['adm_created_by as created_by', 'adm_created_on as created_on', 'adm_updated_by as updated_by', 'adm_updated_on as updated_on'];
        $admin_data = AdminUser::query()->select(array_merge(['admin_users.*'], $created_updated_arr))->where('adm_id', my_decrypt($adm_id))->first();
        if (!empty($admin_data)) {
            return view('admin-user.view-admin-user', compact('admin_data', 'called_from'));
        } else {
            return redirect(route('admin_user_module.list'))->with('error', 'Admin User not found.');
        }
    }
}


