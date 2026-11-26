<?php

namespace App\Services;

use App\Models\MaintenanceSchedule;
use App\Models\ServiceType;
use App\Models\Vehicle;

class ScheduleTemplateService
{
    /**
     * ينسخ القيم الافتراضية كقيم مستقلة لكل سيارة،
     * فتعديل القالب لاحقاً ما يغيّر السيارات الموجودة.
     */
    public function copyDefaultsTo(Vehicle $vehicle): void
    {
        ServiceType::query()->each(function (ServiceType $type) use ($vehicle) {
            MaintenanceSchedule::create([
                'vehicle_id' => $vehicle->id,
                'service_type_id' => $type->id,
                'interval_km' => $type->default_interval_km,
                'interval_months' => $type->default_interval_months,
                'enabled' => true,
            ]);
        });
    }
}
