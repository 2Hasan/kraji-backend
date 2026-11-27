<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceSchedule;
use App\Models\Vehicle;
use App\Services\ReminderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MaintenanceScheduleController extends Controller
{
    /**
     * تذكيرات السيارة: كل أنواع الصيانة مع موعد الجاية، وهل حان وقتها.
     */
    public function index(Request $request, Vehicle $vehicle, ReminderService $reminders): JsonResponse
    {
        $this->authorize('view', $vehicle);

        $today = Carbon::today();

        $schedules = MaintenanceSchedule::where('vehicle_id', $vehicle->id)
            ->with('serviceType')
            ->orderBy('id')
            ->get()
            ->map(fn (MaintenanceSchedule $schedule) => [
                'id' => $schedule->id,
                'service_type_id' => $schedule->service_type_id,
                'service_type_name_ar' => $schedule->serviceType->name_ar,
                'interval_km' => $schedule->interval_km,
                'interval_months' => $schedule->interval_months,
                'enabled' => $schedule->enabled,
                'next_due_km' => $schedule->next_due_km,
                'next_due_date' => $schedule->next_due_date?->toDateString(),
                'is_due' => $schedule->enabled
                    && $reminders->isDue($schedule, (int) $vehicle->current_odometer, $today),
            ]);

        return response()->json($schedules);
    }

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
