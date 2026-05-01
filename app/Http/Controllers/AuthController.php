<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.employee_login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'employee_code' => 'required',
            'password' => 'required',
        ]);

        $credentials = [
            'employee_code' => $request->employee_code,
            'password' => $request->password,
        ];

        if (Auth::attempt($credentials)) {

            if (Auth::user()->type != 'employee') {
                Auth::logout();
                return back()->with('error', 'Only employees can login');
            }

            return redirect()->route('video.index');
        }

        return back()->with('error', 'Invalid credentials');
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }
}
