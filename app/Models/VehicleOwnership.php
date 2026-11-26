<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['vehicle_id', 'user_id', 'started_at', 'ended_at'])]
class VehicleOwnership extends Model
{
    protected $table = 'vehicle_ownerships';

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'ended_at' => 'datetime'];
    }
}
