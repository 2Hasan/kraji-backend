<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['vehicle_id', 'km', 'recorded_at', 'source'])]
class OdometerReading extends Model
{
    protected function casts(): array
    {
        return ['recorded_at' => 'datetime'];
    }
}
