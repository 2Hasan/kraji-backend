<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    /**
     * يسمح لحسابات الإدارة فقط (مشرف أو مراجع موقوف ما يدخل).
     * يمكن تقييد الدور بوسيط: role:admin.
     */
    public function handle(Request $request, Closure $next, ?string $role = null): Response
    {
        $user = $request->user();

        if (! $user instanceof AdminUser || $user->suspended_at !== null) {
            abort(403);
        }

        if ($role !== null && $user->role !== $role && $user->role !== 'admin') {
            abort(403);
        }

        return $next($request);
    }
}
