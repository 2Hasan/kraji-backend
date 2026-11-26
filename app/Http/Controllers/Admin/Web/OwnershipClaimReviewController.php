<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\OwnershipClaim;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\OwnershipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OwnershipClaimReviewController extends Controller
{
    public function index(): View
    {
        $claims = OwnershipClaim::with('vehicle')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->paginate(20);

        return view('admin.claims.index', compact('claims'));
    }

    public function show(OwnershipClaim $claim): View
    {
        $claim->load('vehicle');

        return view('admin.claims.show', [
            'claim' => $claim,
            'claimant' => User::withTrashed()->find($claim->claimant_user_id),
        ]);
    }

    /** إثبات الملكية: يُخدم للإداري المسجّل فقط، بدون رابط عام. */
    public function proof(OwnershipClaim $claim): StreamedResponse
    {
        return Storage::disk(config('filesystems.attachments_disk'))->response($claim->proof_storage_key);
    }

    public function approve(Request $request, OwnershipClaim $claim, OwnershipService $service, AuditLogger $audit): RedirectResponse
    {
        abort_unless($claim->status === 'pending', 422);

        $reviewer = Auth::guard('admin')->user();
        $note = $request->validate(['note' => ['nullable', 'string', 'max:1000']])['note'] ?? null;

        $service->approve($claim, $reviewer, $note);
        $audit->record($reviewer, 'ownership_claim.approved', OwnershipClaim::class, $claim->id, $note);

        return redirect()->route('admin.claims.index')->with('status', 'تمت الموافقة على طلب الملكية.');
    }

    public function reject(Request $request, OwnershipClaim $claim, OwnershipService $service, AuditLogger $audit): RedirectResponse
    {
        abort_unless($claim->status === 'pending', 422);

        $reviewer = Auth::guard('admin')->user();
        $note = $request->validate(['note' => ['required', 'string', 'min:3', 'max:1000']])['note'];

        $service->reject($claim, $reviewer, $note);
        $audit->record($reviewer, 'ownership_claim.rejected', OwnershipClaim::class, $claim->id, $note);

        return redirect()->route('admin.claims.index')->with('status', 'تم رفض طلب الملكية.');
    }
}
