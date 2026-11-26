<?php

namespace App\Services;

use App\Models\ServiceRecord;
use App\Models\User;
use App\Models\Vehicle;

class DataExportService
{
    /** كل سيارات المستخدم وسجلاتها. الشاصي يظهر آخر 4 خانات فقط. */
    public function forUser(User $user): array
    {
        $vehicles = Vehicle::where('owner_user_id', $user->id)->get();

        return [
            'exported_at' => now()->toIso8601String(),
            'vehicles' => $vehicles->map(fn (Vehicle $v) => $v->toArray())->values(),
            'service_records' => ServiceRecord::whereIn('vehicle_id', $vehicles->pluck('id'))
                ->get()
                ->map(fn (ServiceRecord $r) => $r->toArray())
                ->values(),
        ];
    }
}
