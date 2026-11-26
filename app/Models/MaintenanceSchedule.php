<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['vehicle_id', 'service_type_id', 'interval_km', 'interval_months', 'enabled', 'last_service_record_id', 'next_due_km', 'next_due_date'])]
class MaintenanceSchedule extends Model
{
    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'next_due_date' => 'date'];
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class);
    }
}
