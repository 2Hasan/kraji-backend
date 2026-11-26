<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\AuditLog;
use App\Models\ChangeRequest;
use App\Models\OwnershipClaim;
use App\Models\Vehicle;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'pendingRequests' => ChangeRequest::where('status', 'pending')->count(),
            'pendingClaims' => OwnershipClaim::where('status', 'pending')->count(),
            'activeVehicles' => Vehicle::where('status', 'active')->count(),
            'reviewers' => AdminUser::where('role', 'reviewer')->whereNull('suspended_at')->count(),
            'recentLogs' => AuditLog::orderByDesc('id')->limit(6)->get(),
        ]);
    }
}
