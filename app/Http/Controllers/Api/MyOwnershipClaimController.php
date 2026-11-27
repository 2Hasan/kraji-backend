<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OwnershipClaim;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MyOwnershipClaimController extends Controller
{
    /**
     * طلبات الملكية اللي قدّمها المستخدم، مع حالتها وسبب الرفض إن وُجد.
     */
    public function index(Request $request): JsonResponse
    {
        $claims = OwnershipClaim::where('claimant_user_id', $request->user()->id)
            ->with('vehicle:id,make,model,year,status')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (OwnershipClaim $claim) => [
                'id' => $claim->id,
                'vehicle_id' => $claim->vehicle_id,
                'vehicle_title' => trim($claim->vehicle->make.' '.$claim->vehicle->model.' '.($claim->vehicle->year ?? '')),
                'status' => $claim->status,
                'review_note' => $claim->review_note,
                'created_at' => $claim->created_at?->toIso8601String(),
            ]);

        return response()->json($claims);
    }
}
