<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceScheduleController extends Controller
{
    public function update(Request $request, MaintenanceSchedule $schedule): JsonResponse
    {
        abort_unless($schedule->vehicle->owner_user_id === $request->user()->id, 403);

        $data = $request->validate([
            'interval_km' => ['sometimes', 'nullable', 'integer', 'min:1000', 'max:100000'],
            'interval_months' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:120'],
            'enabled' => ['sometimes', 'boolean'],
        ]);

        $schedule->update($data);

        return response()->json($schedule->fresh());
    }
}
