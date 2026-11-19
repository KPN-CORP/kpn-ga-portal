<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\LoginLog;

class LoginController extends Controller
{
    /**
     * Tampilkan halaman login
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Proses login manual (NON SSO)
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $input = $request->username;
        $ip = $request->ip();
        $userAgent = $request->userAgent();

        // Cari user berdasarkan username atau email
        $user = User::where('username', $input)
                    ->orWhere('email', $input)
                    ->first();

        if (!$user) {
            LoginLog::create([
                'username'   => $input,
                'email'      => null,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'status'     => 'failed',
                'message'    => 'User tidak ditemukan'
            ]);
            return back()->withErrors([
                'username' => 'Username atau password salah (login melalui Apps Darwinbox).'
            ]);
        }

        // 🔐 BLOK LOGIN MANUAL UNTUK AKUN SSO
        if ($user->login_type === 'sso') {
            LoginLog::create([
                'user_id'    => $user->id,
                'username'   => $user->username,
                'email'      => $user->email,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'status'     => 'blocked',
                'message'    => 'Percobaan login manual pada akun SSO'
            ]);
            return back()->withErrors([
                'username' => 'Akun ini hanya bisa login melalui Darwinbox.'
            ]);
        }

        // Cek password hash
        if (!Hash::check($request->password, $user->password)) {
            LoginLog::create([
                'user_id'    => $user->id,
                'username'   => $user->username,
                'email'      => $user->email,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'status'     => 'failed',
                'message'    => 'Password salah'
            ]);
            return back()->withErrors([
                'username' => 'Username atau password salah (login melalui Apps Darwinbox)'
            ]);
        }

        // ✅ LOGIN SUKSES
        Auth::login($user);
        $request->session()->regenerate();

        // ⭐⭐⭐ TAMBAHKAN BARIS INI ⭐⭐⭐
        $user->update(['last_login_at' => now()]);

        // Catat log sukses
        LoginLog::create([
            'user_id'    => $user->id,
            'username'   => $user->username,
            'email'      => $user->email,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'status'     => 'success',
            'message'    => null
        ]);

        return redirect()->intended('/dashboard');
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        if (Auth::check()) {
            LoginLog::create([
                'user_id'    => Auth::id(),
                'username'   => Auth::user()->username,
                'email'      => Auth::user()->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status'     => 'logout',
                'message'    => 'User logout'
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}