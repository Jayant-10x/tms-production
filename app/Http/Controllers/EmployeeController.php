<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller {
    public function index(Request $request) {
        $filter_arr = [];
        $employeeBaseQuery = Employee::query();

        if ($request->filled('emp_name')) {
            $employeeBaseQuery->where('emp_full_name', 'like', '%' . $request->emp_name . '%');
            $filter_arr = ['emp_full_name' => $request->emp_name];
        }

        if ($request->filled('emp_designation')) {
            $employeeBaseQuery->where('emp_designation', '=', $request->emp_designation);
            $filter_arr = ['emp_designation' => $request->emp_designation];
        }

        if ($request->filled('emp_dept')) {
            $employeeBaseQuery->where('emp_department', '=', $request->emp_dept);
            $filter_arr = ['emp_dept' => $request->emp_dept];
        }

        if ($request->filled('emp_sub_dept')) {
            $employeeBaseQuery->where('emp_sub_department', '=', $request->emp_sub_dept);
            $filter_arr = ['emp_sub_dept' => $request->emp_sub_dept];
        }

        if ($request->filled('emp_status')) {
            $employeeBaseQuery->where('emp_status', '=', $request->emp_status);
            $filter_arr = ['emp_status' => $request->emp_status];
        }

        $all_employees = $employeeBaseQuery->paginate(config('constants.PER_PAGE_ITEM_COUNT'), pageName: "employees")->withQueryString();
        return view('employees.list', compact('all_employees', 'filter_arr'));
    }

    public function add(Request $request) {
        $mode = 'add';

        //        session()->flash('success', 'Employee added successfully.');
        return view('employees.add_edit_employee', compact('mode'));
    }

    public function store(Request $request) {
        $request->validate([
            'emp_id' => 'required|unique:employees,emp_internal_id',
            'full_name' => 'required|min:' . MIN_LENGTH . '|max:' . MAX_LENGTH_100,
            'phone_number' => 'required|min:' . MIN_LENGTH_10 . '|max:' . MAX_LENGTH_20 . "|unique:employees,emp_phone_number",
            'email' => 'required|email|min:' . MIN_LENGTH . '|max:250|unique:employees,emp_email',
            'employment_type' => 'required',
            'designation' => 'required',
            'department' => 'required',
            'sub_department' => 'nullable',
            'reporting_to' => 'nullable',
            'joining_date' => 'required|date',
            'status' => 'required',
            'employee_photo' => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
        ]);

        $current_date = date(config('constants.DB_DATE_TIME_FORMAT'));

        $employee = new Employee();
        $employee->emp_internal_id = $request->emp_id;
        $employee->emp_full_name = $request->full_name;
        $employee->emp_phone_number = $request->phone_number;
        $employee->emp_email = $request->email;
        $employee->emp_employment_type = $request->employment_type;
        $employee->emp_designation = $request->designation;
        $employee->emp_department = $request->department;
        $employee->emp_sub_department = $request->sub_department;
        $employee->emp_reporting_to = $request->reporting_to;
        $employee->emp_joining_date = $request->joining_date;
        $employee->emp_status = $request->status;
        if ($request->hasFile('employee_photo')) {
            $file = $request->file('employee_photo');

            // Slugify the original name and append a unique identifier
            $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                . '_' . uniqid()
                . '.' . $file->getClientOriginalExtension();
            $employee->emp_photo = $filename;
            $file->storeAs('employees', $filename, 'public'); // it returns the image path
        }
        $employee->emp_created_by = setCreatedUpdatedBy();
        $employee->emp_created_on = $current_date;
        $is_saved = $employee->save();

        if ($is_saved) {
            return redirect(route('employees.list', $request->query()))->with('success', 'Employee added successfully.');
        } else {
            return redirect(route('employees.list', $request->query()))->with('error', 'Something went wrong.');
        }
    }

    public function view($emp_id) {
        $emp_id = my_decrypt($emp_id);
        $created_updated_arr = ['emp_created_by as created_by', 'emp_created_on as created_on', 'emp_updated_by as updated_by', 'emp_updated_on as updated_on'];

        $emp_data = Employee::query()->select(array_merge(['employees.*'], $created_updated_arr))->with(['reporting_to:emp_full_name,emp_id', 'admin_user_details:adm_emp_id,adm_user_name,adm_role,adm_created_by as created_by,adm_created_on as created_on,adm_updated_by as updated_by,adm_updated_on as updated_on'])->where('emp_id', $emp_id)->first();

        if (!empty($emp_data)) {
            $called_from = 'employee';

            return view('employees.view_employee', compact('emp_data', 'called_from'));
        } else {
            return redirect()->back()->with('error', 'Employee not found.');
        }
    }

    public function edit(Request $request, $emp_id) {
        $emp_id = my_decrypt($emp_id);
        $emp_data = Employee::query()->where('emp_id', $emp_id)->first();
        $admin_data = AdminUser::query()->where('adm_emp_id', $emp_id)->first();
        $mode = 'edit';

        if (!empty($emp_data)) {
            return view('employees.add_edit_employee', compact('emp_data', 'admin_data', 'mode'));
        } else {
            return redirect(route('employees.list', $request->query()))->with('error', 'Employee not found.');
        }
    }

    public function update(Request $request, $emp_id) {
        $emp_id = my_decrypt($emp_id);

        $request->validate([
            'emp_id' => ['required',
                Rule::unique('employees', 'emp_internal_id')->ignore($emp_id, 'emp_id'),
            ],
            'full_name' => 'required|min:' . MIN_LENGTH . '|max:' . MAX_LENGTH_100,
            'phone_number' => ['required', 'min:' . MIN_LENGTH_10, "max:" . MAX_LENGTH_20,
                Rule::unique('employees', 'emp_phone_number')->ignore($emp_id, 'emp_id'),
            ],
            'email' => ['required', 'email', 'min:' . MIN_LENGTH, 'max:250',
                Rule::unique('employees', 'emp_email')->ignore($emp_id, 'emp_id'),
            ],
            'employment_type' => 'required',
            'designation' => 'required',
            'department' => 'required',
            'sub_department' => 'nullable',
            'reporting_to' => 'nullable',
            'joining_date' => 'required|date',
            'status' => 'required',
            'employee_photo' => 'nullable|file|mimes:jpeg,jpg,png|max:5120',
        ]);

        try {
            DB::transaction(function () use ($request, $emp_id) {
                $current_date = date(config('constants.DB_DATE_TIME_FORMAT'));
                $employee = Employee::where('emp_id', $emp_id)->firstOrFail();

                $employee->emp_internal_id = $request->emp_id;
                $employee->emp_full_name = $request->full_name;
                $employee->emp_phone_number = $request->phone_number;
                $employee->emp_email = $request->email;
                $employee->emp_employment_type = $request->employment_type;
                $employee->emp_designation = $request->designation;
                $employee->emp_department = $request->department;
                $employee->emp_sub_department = $request->sub_department;
                $employee->emp_reporting_to = $request->reporting_to;
                $employee->emp_joining_date = $request->joining_date;
                $employee->emp_status = $request->status;
                $employee->emp_updated_by = setCreatedUpdatedBy();
                $employee->emp_updated_on = $current_date;

                /*
                 * Employee photo
                 */
                if ($request->hasFile('employee_photo')) {
                    // Delete old photo
                    if (!empty($employee->emp_photo)) {
                        Storage::disk('public')->delete(
                            'employees/' . $employee->emp_photo
                        );
                    }
                    $file = $request->file('employee_photo');

                    $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $employee->emp_photo = $filename;
                    $file->storeAs('employees', $filename, 'public');
                } else {
                    if (!empty($employee->emp_photo)) {
                        Storage::disk('public')->delete('employees/' . $employee->emp_photo);
                    }
                    $employee->emp_photo = null;
                }
                $employee->save();

                // to sync the code with admin user table
                $isAdminDetailsExist = AdminUser::query()->where('adm_emp_id', '=', $employee->emp_id)->first();
                if (!empty($isAdminDetailsExist)) {
                    $isAdminDetailsExist->adm_name = $employee->emp_full_name;
                    $isAdminDetailsExist->adm_status = $employee->emp_status;
                    $isAdminDetailsExist->save();
                }
            });

            return redirect(route('employees.edit', array_merge(['emp_id' => my_encrypt($emp_id)], $request->query())))->with('success', 'Employee updated successfully.');

        } catch (\Throwable $e) {
            \Log::error('Employee update failed', [
                'emp_id' => $emp_id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return redirect(route('employees.edit', array_merge(['emp_id' => my_encrypt($emp_id)], $request->query())))->with('error', 'Something went wrong.');
        }
    }
}
