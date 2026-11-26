<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\OwnershipClaim;
use App\Models\Vehicle;
use App\Services\OwnershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OwnershipClaimController extends Controller
{
    /** بحث بالشاصي الكامل: يرجع الاسم والموديل فقط للسيارة بدون مالك. */
    public function lookup(Request $request): JsonResponse
    {
        $data = $request->validate(['chassis_no' => ['required', 'string', 'size:17']]);

        $vehicle = Vehicle::where('chassis_hash', Vehicle::hashChassis($data['chassis_no']))
            ->where('status', 'active')
            ->whereNull('owner_user_id')
            ->first();

        abort_unless($vehicle, 404);

        return response()->json($vehicle->only(['id', 'make', 'model', 'year']));
    }

    public function store(Request $request, Vehicle $vehicle, OwnershipService $service): JsonResponse
    {
        $request->validate(['proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192']]);

        $file = $request->file('proof');
        $key = Storage::disk('local')->putFile('ownership-proofs/'.$vehicle->id, $file);

        $claim = $service->submitClaim($vehicle, $request->user(), $key);

        return response()->json($claim, 201);
    }

    public function approve(Request $request, OwnershipClaim $claim, OwnershipService $service): JsonResponse
    {
        abort_unless($claim->status === 'pending', 422);

        $service->approve($claim, $request->user(), $request->input('note'));

        return response()->json($claim->fresh());
    }

    public function reject(Request $request, OwnershipClaim $claim, OwnershipService $service): JsonResponse
    {
        abort_unless($claim->status === 'pending', 422);

        $data = $request->validate(['note' => ['required', 'string', 'min:3', 'max:1000']]);
        $service->reject($claim, $request->user(), $data['note']);

        return response()->json($claim->fresh());
    }
}
