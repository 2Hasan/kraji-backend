<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['code', 'name', 'body_type', 'svg_storage_key', 'status', 'validation_errors'])]
class PlateTemplate extends Model
{
    /**
     * رمز داخلي فريد للقالب. ما يظهر للمستخدم، والاسم هو نوع السيارة نفسه.
     */
    public static function newCode(): string
    {
        return 'tpl-'.strtolower(Str::random(10));
    }

    protected function casts(): array
    {
        return ['validation_errors' => 'array'];
    }
}
