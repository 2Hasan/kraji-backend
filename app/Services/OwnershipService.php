<?php

namespace App\Services;

use App\Models\AdminUser;
use App\Models\OwnershipClaim;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleOwnership;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OwnershipService
{
    public function submitClaim(Vehicle $vehicle, User $claimant, string $proofStorageKey): OwnershipClaim
    {
        if ($vehicle->owner_user_id !== null) {
            throw ValidationException::withMessages([
                'vehicle' => ['هالسيارة لها مالك حالياً.'],
            ]);
        }

        return OwnershipClaim::create([
            'vehicle_id' => $vehicle->id,
            'claimant_user_id' => $claimant->id,
            'proof_storage_key' => $proofStorageKey,
            'status' => 'pending',
        ]);
    }

    public function approve(OwnershipClaim $claim, AdminUser $reviewer, ?string $note = null): void
    {
        DB::transaction(function () use ($claim, $reviewer, $note) {
            $vehicle = $claim->vehicle;

            VehicleOwnership::create([
                'vehicle_id' => $vehicle->id,
                'user_id' => $claim->claimant_user_id,
                'started_at' => now(),
            ]);

            $vehicle->update(['owner_user_id' => $claim->claimant_user_id, 'status' => 'active']);

            $claim->update([
                'status' => 'approved',
                'reviewed_by_admin_id' => $reviewer->id,
                'review_note' => $note,
                'reviewed_at' => now(),
            ]);
        });
    }

    public function reject(OwnershipClaim $claim, AdminUser $reviewer, string $note): void
    {
        $claim->update([
            'status' => 'rejected',
            'reviewed_by_admin_id' => $reviewer->id,
            'review_note' => $note,
            'reviewed_at' => now(),
        ]);
    }
}
