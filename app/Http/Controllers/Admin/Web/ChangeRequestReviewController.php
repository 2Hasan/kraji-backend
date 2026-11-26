<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\ChangeRequest;
use App\Services\AuditLogger;
use App\Services\ChangeRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ChangeRequestReviewController extends Controller
{
    public function index(): View
    {
        $requests = ChangeRequest::with('serviceRecord.vehicle')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->paginate(20);

        return view('admin.requests.index', compact('requests'));
    }

    public function show(ChangeRequest $changeRequest): View
    {
        $changeRequest->load('serviceRecord.vehicle');

        return view('admin.requests.show', ['cr' => $changeRequest]);
    }

    public function approve(Request $request, ChangeRequest $changeRequest, ChangeRequestService $service, AuditLogger $audit): RedirectResponse
    {
        abort_unless($changeRequest->status === 'pending', 422);

        $reviewer = Auth::guard('admin')->user();
        $note = $request->validate(['note' => ['nullable', 'string', 'max:1000']])['note'] ?? null;

        $service->approve($changeRequest, $reviewer, $note);
        $audit->record($reviewer, 'change_request.approved', ChangeRequest::class, $changeRequest->id, $note);

        return redirect()->route('admin.requests.index')->with('status', 'تمت الموافقة على الطلب.');
    }

    public function reject(Request $request, ChangeRequest $changeRequest, ChangeRequestService $service, AuditLogger $audit): RedirectResponse
    {
        abort_unless($changeRequest->status === 'pending', 422);

        $reviewer = Auth::guard('admin')->user();
        $note = $request->validate(['note' => ['required', 'string', 'min:3', 'max:1000']])['note'];

        $service->reject($changeRequest, $reviewer, $note);
        $audit->record($reviewer, 'change_request.rejected', ChangeRequest::class, $changeRequest->id, $note);

        return redirect()->route('admin.requests.index')->with('status', 'تم رفض الطلب.');
    }
}
