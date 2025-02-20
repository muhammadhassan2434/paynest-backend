<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class Authcontroller extends Controller
{

    public function login()
    {
        return view('admin.auth.login');
    }

    public function auth(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => ['required', Password::min(8)->mixedCase()->symbols()->uncompromised()]
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->errors())->withInput();
        }

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            if (Auth::user()->status !== 'active') {
                Auth::logout();
                return back()->with('error', 'Your account is inactive. Contact support.');
            }

            if (Auth::user()->role !== 'admin') {
                Auth::logout();
                return back()->with('error', 'Only Admin can login');
            }
            return redirect()->route('dashboard.index');
        }

        return back()->with('error', 'Invalid email or password.');
    }

    public function logout(){
        Auth::logout();
        return redirect()->route('admin.login');
    }
}
