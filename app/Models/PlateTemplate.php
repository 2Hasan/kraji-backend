<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'name', 'body_type', 'svg_storage_key', 'status', 'validation_errors'])]
class PlateTemplate extends Model
{
    protected function casts(): array
    {
        return ['validation_errors' => 'array'];
    }
}
