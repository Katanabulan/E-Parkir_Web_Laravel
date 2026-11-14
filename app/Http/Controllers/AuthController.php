<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GoogleAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            } elseif ($user->role === 'petugas') {
                return redirect()->route('admin.monitoring');
            }
            Auth::logout();
            return redirect()->route('login')->withErrors([
                'email' => 'Portal web ini khusus untuk Administrator dan Petugas. Akun Pengendara dan Owner silakan masuk melalui aplikasi Mobile.'
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
                // Khusus portal Web, hanya izinkan role admin dan petugas
                if (!in_array($user->role, ['admin', 'petugas'])) {
                    return back()->withInput($request->only('email'))->withErrors([
                        'email' => 'Akses portal web hanya untuk Administrator dan Petugas. Akun ' . ucfirst($user->role) . ' silakan masuk melalui aplikasi Mobile.',
                    ]);
                }

                // Pastikan password tersimpan sebagai hash Bcrypt yang aman
                if (Hash::needsRehash($user->password) || !str_starts_with($user->password, '$2y$')) {
                    $user->password = Hash::make($credentials['password']);
                    $user->save();
                }

                Auth::login($user, $request->boolean('remember'));
                $request->session()->regenerate();

                if ($user->role === 'petugas') {
                    return redirect()->intended(route('admin.monitoring'));
                }

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

    public function redirectToGoogle(Request $request, GoogleAuthService $google)
    {
        if (!$google->isWebConfigured()) {
            return redirect()->route('login')->with('error', 'Konfigurasi Google OAuth (GOOGLE_CLIENT_ID & GOOGLE_CLIENT_SECRET) belum diatur di .env.');
        }

        $redirectUri = $this->resolveGoogleRedirectUri();
        $targetScheme = parse_url($redirectUri, PHP_URL_SCHEME) ?: $request->getScheme();
        $targetHost = parse_url($redirectUri, PHP_URL_HOST);
        $targetPort = (int) (parse_url($redirectUri, PHP_URL_PORT) ?: ($targetScheme === 'https' ? 443 : 80));
        $currentPort = (int) ($request->getPort() ?: ($request->isSecure() ? 443 : 80));

        // Pastikan cookie session state dibuat di host/port yang sama dengan callback redirect_uri
        if ($targetHost && (strcasecmp($request->getHost(), $targetHost) !== 0 || $currentPort !== $targetPort)) {
            $portSuffix = (($targetScheme === 'http' && $targetPort === 80) || ($targetScheme === 'https' && $targetPort === 443))
                ? ''
                : ':' . $targetPort;
            return redirect()->away("{$targetScheme}://{$targetHost}{$portSuffix}" . route('auth.google', [], false));
        }

        $state = Str::random(40);
        $request->session()->put('google_oauth_state', $state);
        $request->session()->save();

        return redirect()->away($google->buildAuthorizationUrl($state, $redirectUri));
    }

    public function handleGoogleCallback(Request $request, GoogleAuthService $google)
    {
        if ($request->filled('error')) {
            return redirect()->route('login')->with('error', 'Login dengan Google dibatalkan atau ditolak.');
        }

        $expectedState = (string) $request->session()->pull('google_oauth_state', '');
        $actualState = (string) $request->query('state', '');
        if ($expectedState === '' || !hash_equals($expectedState, $actualState)) {
            return redirect()->route('login')->with('error', 'Sesi otentikasi Google tidak valid. Silakan coba lagi.');
        }

        $code = trim((string) $request->query('code', ''));
        if ($code === '') {
            return redirect()->route('login')->with('error', 'Kode otorisasi Google tidak ditemukan.');
        }

        try {
            $googleUser = $google->exchangeCodeForUserInfo($code, $this->resolveGoogleRedirectUri());
        } catch (\Throwable $e) {
            return redirect()->route('login')->with('error', $e->getMessage() ?: 'Gagal memverifikasi akun Google.');
        }

        $user = User::where('google_id', $googleUser['google_id'])
            ->orWhereRaw('LOWER(email) = ?', [$googleUser['email']])
            ->first();

        if (!$user) {
            return redirect()->route('login')->with('error', "Akun Google ({$googleUser['email']}) belum terdaftar sebagai Administrator. Untuk pendaftaran pengendara, silakan gunakan aplikasi Mobile.");
        }

        if (!in_array($user->role, ['admin', 'petugas'])) {
            return redirect()->route('login')->with('error', 'Akses portal web hanya untuk Administrator dan Petugas. Akun ' . ucfirst($user->role) . ' silakan masuk melalui aplikasi Mobile.');
        }

        $updates = [];
        if (empty($user->google_id)) {
            $updates['google_id'] = $googleUser['google_id'];
        }
        if (empty($user->avatar) && !empty($googleUser['avatar'])) {
            $updates['avatar'] = $googleUser['avatar'];
        }
        if (!empty($updates)) {
            $user->update($updates);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    private function resolveGoogleRedirectUri(): string
    {
        $configured = trim((string) config('services.google.redirect', ''));
        if ($configured !== '') {
            return str_starts_with($configured, 'http') ? $configured : url($configured);
        }

        return route('auth.google.callback');
    }
}
