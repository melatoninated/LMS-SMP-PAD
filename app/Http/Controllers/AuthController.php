<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login()
    {
        return view('auth.login');
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate(['username' => ['required'], 'password' => ['required']]);

        if (! Auth::attempt([...$credentials, 'is_active' => true])) {
            return back()->withErrors(['username' => 'Username atau password tidak sesuai.'])->onlyInput('username');
        }

        $request->session()->regenerate();
        AuditLog::create(['user_id' => Auth::id(), 'event' => 'login', 'created_at' => now()]);

        if (Auth::user()->must_change_password) {
            return redirect()->route('password.change');
        }

        return redirect()->route(Auth::user()->role.'.dashboard');
    }

    public function logout(Request $request)
    {
        AuditLog::create(['user_id' => Auth::id(), 'event' => 'logout', 'created_at' => now()]);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function changePassword()
    {
        return view('auth.change-password');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $request->user()->update(['password' => Hash::make($data['password']), 'must_change_password' => false]);
        AuditLog::create(['user_id' => Auth::id(), 'event' => 'password_changed', 'created_at' => now()]);

        return redirect()->route($request->user()->role.'.dashboard')->with('status', 'Password berhasil diubah.');
    }
}
