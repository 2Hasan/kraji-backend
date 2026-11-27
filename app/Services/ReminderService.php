<?php

namespace App\Services;

use App\Models\MaintenanceSchedule;
use App\Models\OdometerReading;
use App\Models\ServiceRecord;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;

class ReminderService
{
    public const KM_THRESHOLD = 500;

    public const DAYS_THRESHOLD = 14;

    public const ODOMETER_PROMPT_DAYS = 14;

    public function isDue(MaintenanceSchedule $schedule, int $currentKm, Carbon $today): bool
    {
        if ($schedule->next_due_km !== null && $schedule->next_due_km - $currentKm <= self::KM_THRESHOLD) {
            return true;
        }

        if ($schedule->next_due_date !== null) {
            $days = $today->copy()->startOfDay()->diffInDays(Carbon::parse($schedule->next_due_date)->startOfDay(), false);

            return $days <= self::DAYS_THRESHOLD;
        }

        return false;
    }

    /**
     * بعد تسجيل صيانة: نحسب موعد الصيانة الجاية لكل جدول بنفس النوع.
     * الكيلومترات من العداد وقت الصيانة، والتاريخ من تاريخ الصيانة.
     */
    public function markServiced(ServiceRecord $record): void
    {
        MaintenanceSchedule::where('vehicle_id', $record->vehicle_id)
            ->where('service_type_id', $record->service_type_id)
            ->get()
            ->each(function (MaintenanceSchedule $schedule) use ($record) {
                $schedule->update([
                    'last_service_record_id' => $record->id,
                    'next_due_km' => $schedule->interval_km !== null ? $record->odometer + $schedule->interval_km : null,
                    'next_due_date' => $schedule->interval_months !== null
                        ? $record->service_date->copy()->addMonths($schedule->interval_months)
                        : null,
                ]);
            });
    }

    /** نطلب تحديث العداد فقط إذا آخر قراءة قبل 14 يوم أو أكثر، ومرة كل 14 يوم كحد أقصى. */
    public function needsOdometerPrompt(Vehicle $vehicle, Carbon $now): bool
    {
        $last = OdometerReading::where('vehicle_id', $vehicle->id)->orderByDesc('recorded_at')->first();

        if ($last === null) {
            return true;
        }

        return $last->recorded_at->lte($now->copy()->subDays(self::ODOMETER_PROMPT_DAYS));
    }
}
