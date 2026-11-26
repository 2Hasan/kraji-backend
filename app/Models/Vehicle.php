<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['owner_user_id', 'chassis_no', 'make', 'model', 'year', 'trim', 'body_type', 'engine', 'transmission', 'color', 'plate_number', 'plate_style', 'plate_visible', 'nickname', 'current_odometer', 'status', 'source'])]
#[Hidden(['chassis_no', 'chassis_hash'])]
class Vehicle extends Model
{
    use HasFactory;

    protected $appends = ['chassis_last4'];

    protected function casts(): array
    {
        return ['chassis_no' => 'encrypted', 'plate_visible' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (Vehicle $vehicle) {
            if ($vehicle->isDirty('chassis_no') && $vehicle->chassis_no) {
                $vehicle->chassis_hash = self::hashChassis($vehicle->chassis_no);
            }
        });
    }

    public static function hashChassis(string $chassis): string
    {
        return hash_hmac('sha256', strtoupper(trim($chassis)), config('app.key'));
    }

    public function serviceRecords()
    {
        return $this->hasMany(ServiceRecord::class);
    }

    public function getChassisLast4Attribute(): ?string
    {
        return $this->chassis_no ? substr($this->chassis_no, -4) : null;
    }
}
