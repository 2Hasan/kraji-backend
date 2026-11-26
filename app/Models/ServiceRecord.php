<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['vehicle_id', 'created_by_user_id', 'service_type_id', 'service_date', 'odometer', 'cost', 'currency', 'workshop_name', 'notes', 'source', 'status', 'void_reason', 'voided_at'])]
class ServiceRecord extends Model
{
    protected $appends = [];

    protected $with = ['attachments'];

    protected function casts(): array
    {
        return ['service_date' => 'date', 'cost' => 'decimal:3', 'voided_at' => 'datetime'];
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function attachments()
    {
        return $this->hasMany(ServiceRecordAttachment::class);
    }
}
