<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }
            Auth::logout();
            return redirect()->route('login')->withErrors([
                'email' => 'Portal web ini khusus untuk Administrator. Akun Pengendara dan Owner silakan masuk melalui aplikasi Mobile.'
            ]);
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'Email wajib diisi!',
            'email.email' => 'Format email tidak valid!',
            'password.required' => 'Password wajib diisi!',
        ]);

        $user = User::whereRaw('LOWER(email) = ?', [strtolower(trim($credentials['email']))])->first();

        if ($user) {
            $isPasswordValid = false;
            try {
                $isPasswordValid = Hash::check($credentials['password'], $user->password) 
                                || password_verify($credentials['password'], $user->password);
            } catch (\Throwable $e) {
                // Fallback jika password diisi teks biasa di phpMyAdmin
                if ($user->password === $credentials['password']) {
                    $isPasswordValid = true;
                }
            }

            if (!$isPasswordValid && $user->password === $credentials['password']) {
                $isPasswordValid = true;
            }

            if ($isPasswordValid) {
                // Khusus portal Web, hanya izinkan role admin
                if ($user->role !== 'admin') {
                    return back()->withInput($request->only('email'))->withErrors([
                        'email' => 'Akses portal web hanya untuk Administrator. Akun ' . ucfirst($user->role) . ' silakan masuk melalui aplikasi Mobile.',
                    ]);
                }

                // Pastikan password tersimpan sebagai hash Bcrypt yang aman
                if (Hash::needsRehash($user->password) || !str_starts_with($user->password, '$2y$')) {
                    $user->password = Hash::make($credentials['password']);
                    $user->save();
                }

                Auth::login($user, $request->boolean('remember'));
                $request->session()->regenerate();

                return redirect()->intended(route('admin.dashboard'));
            }
        }

        return back()->withInput($request->only('email'))->withErrors([
            'email' => 'Email atau password yang Anda masukkan salah!',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar dari sistem.');
    }
}
