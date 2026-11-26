<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\AuditLog;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $admin = AdminUser::where('email', $data['email'])->first();

        if (! $admin || ! Hash::check($data['password'], $admin->password)) {
            throw ValidationException::withMessages(['email' => ['البيانات غير صحيحة.']]);
        }

        if ($admin->suspended_at !== null) {
            abort(403, 'الحساب موقوف.');
        }

        return response()->json([
            'token' => $admin->createToken('admin')->plainTextToken,
            'role' => $admin->role,
        ]);
    }

    /** المشرف فقط: إضافة مراجع. */
    public function createReviewer(Request $request, AuditLogger $audit): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:admins,email'],
            'password' => ['required', 'string', 'min:12'],
        ]);

        $reviewer = AdminUser::create($data + ['role' => 'reviewer']);
        $audit->record($request->user(), 'reviewer.created', AdminUser::class, $reviewer->id);

        return response()->json(['id' => $reviewer->id, 'role' => 'reviewer'], 201);
    }

    /** المشرف فقط: إيقاف مراجع. */
    public function suspendReviewer(Request $request, AdminUser $reviewer, AuditLogger $audit): JsonResponse
    {
        abort_if($reviewer->role === 'admin', 422);

        $reviewer->update(['suspended_at' => now()]);
        $audit->record($request->user(), 'reviewer.suspended', AdminUser::class, $reviewer->id);

        return response()->json(['ok' => true]);
    }

    /** المشرف فقط: قراءة سجل التدقيق (لا يوجد تعديل أو حذف). */
    public function auditLogs(): JsonResponse
    {
        return response()->json(AuditLog::orderByDesc('id')->limit(200)->get());
    }
}
