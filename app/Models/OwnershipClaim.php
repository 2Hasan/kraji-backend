<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['vehicle_id', 'claimant_user_id', 'proof_storage_key', 'status', 'reviewed_by_admin_id', 'review_note', 'reviewed_at'])]
class OwnershipClaim extends Model
{
    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
