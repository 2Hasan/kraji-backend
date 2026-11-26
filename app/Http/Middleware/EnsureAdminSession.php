<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * يحمي صفحات لوحة الإدارة (ويب) بجلسة حارس admin.
 * الحساب الموقوف يُخرج من الجلسة فوراً.
 */
class EnsureAdminSession
{
    public function handle(Request $request, Closure $next, ?string $role = null): Response
    {
        $admin = Auth::guard('admin')->user();

        if ($admin === null) {
            return redirect()->route('admin.login');
        }

        if ($admin->suspended_at !== null) {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();

            return redirect()->route('admin.login')->withErrors(['email' => 'الحساب موقوف.']);
        }

        if ($role !== null && $admin->role !== $role && $admin->role !== 'admin') {
            abort(403);
        }

        return $next($request);
    }
}
