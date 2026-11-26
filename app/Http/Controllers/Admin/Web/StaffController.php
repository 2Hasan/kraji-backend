<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\AuditLog;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'audit' ? 'audit' : 'reviewers';

        return view('admin.staff.index', [
            'tab' => $tab,
            'reviewers' => AdminUser::where('role', 'reviewer')->orderBy('name')->get(),
            'logs' => $tab === 'audit' ? AuditLog::orderByDesc('id')->limit(200)->get() : collect(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:admins,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        $reviewer = AdminUser::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'reviewer',
        ]);

        $audit->record(Auth::guard('admin')->user(), 'reviewer.created', AdminUser::class, $reviewer->id);

        return redirect()->route('admin.staff.index')->with('status', 'تمت إضافة المراجع.');
    }

    public function suspend(AdminUser $reviewer, AuditLogger $audit): RedirectResponse
    {
        abort_if($reviewer->role === 'admin', 422);

        $reviewer->update(['suspended_at' => now()]);
        $audit->record(Auth::guard('admin')->user(), 'reviewer.suspended', AdminUser::class, $reviewer->id);

        return redirect()->route('admin.staff.index')->with('status', 'تم إيقاف المراجع.');
    }
}
