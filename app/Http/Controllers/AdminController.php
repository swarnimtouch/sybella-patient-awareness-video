<?php

namespace App\Http\Controllers;

use App\Exports\DoctorExport;
use App\Exports\EmployeeExport;
use App\Models\User;
use App\Models\UserFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;


class AdminController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('admin.doctors.index');
        }

        return view('auth.admin_login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');
        $remember    = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            return redirect()->route('admin.dashboard');
        }

        return back()->withErrors([
            'email' => 'Invalid email or password',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    public function dashboard()
    {
        $totalEmployee = User::where('type', 'employee')->count();

        $totalDoctor = User::where('type', 'doctor')->count();

        $totalUserFiles = UserFile::count();

        return view('admin.dashboard', compact(
            'totalEmployee',
            'totalDoctor',
            'totalUserFiles'
        ));
    }



    public function doctor(Request $request)
    {
        $doctors = User::where('type', 'doctor')
            ->whereHas('userFile')
            ->with(['userFile', 'employee'])

            ->when($request->search, function ($q) use ($request) {
                $search = $request->search;

                $q->where(function ($query) use ($search) {

                    // 🔍 Doctor fields
                    $query->where('name', 'like', "%$search%")
                        ->orWhere('mobile', 'like', "%$search%")
                        ->orWhere('speciality', 'like', "%$search%")
                        ->orWhere('hospital_name', 'like', "%$search%")
                        ->orWhere('address', 'like', "%$search%")

                        // 🔍 Employee fields
                        ->orWhereHas('employee', function ($q2) use ($search) {
                            $q2->where('name', 'like', "%$search%")
                                ->orWhere('employee_code', 'like', "%$search%");
                        });
                });
            })

            ->latest()
            ->paginate(10);

        return view('admin.doctors', compact('doctors'));
    }

    public function doctor_destroy($id)
    {
        $doctor = User::where('type', 'doctor')->findOrFail($id);

        UserFile::where('user_id', $doctor->id)->delete();

        $doctor->delete();

        return redirect()->route('admin.doctors.index')
            ->with('success', 'Doctor deleted successfully');
    }

    public function doctor_export(Request $request)
    {
        return Excel::download(
            new DoctorExport($request->search),
            'doctor.xlsx'
        );
    }



    public function employee(Request $request)
    {
        $employees = User::where('type', 'employee')
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('employee_code', 'like', '%' . $request->search . '%')
                        ->orWhere('position_code', 'like', '%' . $request->search . '%')
                        ->orWhere('designation', 'like', '%' . $request->search . '%')
                        ->orWhere('hq_name', 'like', '%' . $request->search . '%')
                        ->orWhere('hq_code', 'like', '%' . $request->search . '%');
                });
            })
            ->latest()
            ->paginate(10);

        return view('admin.employee', compact('employees'));
    }


    public function employee_destroy($id)
    {
        $employee = User::where('type', 'employee')->findOrFail($id);

        $employee->delete();

        return redirect()->route('admin.employees.index')
            ->with('success', 'Employee deleted successfully');
    }

    public function employee_export(Request $request)
    {
        return Excel::download(
            new EmployeeExport($request->search),
            'employee.xlsx'
        );
    }

}
