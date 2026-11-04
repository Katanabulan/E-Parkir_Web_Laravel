<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['status' => 'error', 'message' => 'Unauthenticated'], 401);
            }
            return redirect()->route('login');
        }

        $user = Auth::user();

        if (!in_array($user->role, $roles)) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized role'], 403);
            }

            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard')->with('error', 'Akses ditolak: Anda terautentikasi sebagai Administrator.');
            }

            if ($user->role === 'owner') {
                return redirect()->route('owner.reports.index')->with('error', 'Akses ditolak: Akun Owner hanya memiliki hak akses ke Portal Laporan.');
            }

            return redirect()->route('client.dashboard')->with('error', 'Akses ditolak.');
        }

        return $next($request);
    }
}
