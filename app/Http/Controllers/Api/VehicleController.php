<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlateTemplate;
use App\Models\Vehicle;
use App\Services\ReminderService;
use App\Services\ScheduleTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VehicleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $reminders = app(ReminderService::class);
        $now = now();

        $vehicles = Vehicle::where('owner_user_id', $request->user()->id)
            ->get()
            ->map(fn (Vehicle $vehicle) => $vehicle->toArray() + [
                // يطلب تحديث العداد فقط إذا آخر قراءة قبل 14 يوم أو ما فيه قراءة
                'needs_odometer_update' => $vehicle->status === 'active'
                    && $reminders->needsOdometerPrompt($vehicle, $now),
            ]);

        return response()->json($vehicles);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'chassis_no' => ['required', 'string', 'size:17'],
            'make' => ['required', 'string', 'max:60'],
            'model' => ['required', 'string', 'max:60'],
            'year' => ['nullable', 'integer', 'between:1950,2100'],
            'current_odometer' => ['nullable', 'integer', 'min:0'],
            'body_type' => ['nullable', 'string', 'max:40'],
            'plate_number' => ['nullable', 'string', 'max:20'],
            'plate_style' => ['sometimes', 'string', 'max:40', $this->plateStyleRule($request->input('body_type'))],
            'plate_visible' => ['sometimes', 'boolean'],
            'nickname' => ['nullable', 'string', 'max:60'],
        ]);

        $hash = Vehicle::hashChassis($data['chassis_no']);
        if (Vehicle::where('chassis_hash', $hash)->where('status', 'active')->exists()) {
            // رسالة عامة: ما نكشف مالك السيارة الثانية
            throw ValidationException::withMessages([
                'chassis_no' => ['هالشاصي مسجّل مسبقاً.'],
            ]);
        }

        $vehicle = Vehicle::create($data + [
            'owner_user_id' => $request->user()->id,
            'status' => 'active',
            'source' => 'manual',
        ]);

        app(ScheduleTemplateService::class)->copyDefaultsTo($vehicle);

        return response()->json($vehicle->fresh(), 201);
    }

    public function show(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->authorize('view', $vehicle);

        return response()->json($vehicle);
    }

    public function update(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->authorize('update', $vehicle);

        $bodyType = $request->input('body_type', $vehicle->body_type);

        $data = $request->validate([
            'make' => ['sometimes', 'string', 'max:60'],
            'model' => ['sometimes', 'string', 'max:60'],
            'year' => ['sometimes', 'nullable', 'integer', 'between:1950,2100'],
            'body_type' => ['sometimes', 'nullable', 'string', 'max:40'],
            'plate_number' => ['sometimes', 'nullable', 'string', 'max:20'],
            'plate_style' => ['sometimes', 'string', 'max:40', $this->plateStyleRule($bodyType)],
            'plate_visible' => ['sometimes', 'boolean'],
            'nickname' => ['sometimes', 'nullable', 'string', 'max:60'],
        ]);

        $vehicle->update($data);

        return response()->json($vehicle->fresh());
    }

    /**
     * النمط إما من الأنماط الثلاثة القديمة، أو كود قالب مفعّل تابع لنوع الهيكل نفسه.
     *
     * @return \Closure(string, mixed, \Closure): void
     */
    private function plateStyleRule(?string $bodyType): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($bodyType): void {
            if (in_array($value, ['classic', 'carbon', 'blue'], true)) {
                return;
            }

            $exists = PlateTemplate::where('code', $value)
                ->where('status', 'active')
                ->where('body_type', $bodyType)
                ->exists();

            if (! $exists) {
                $fail('القالب المختار غير متوفر لنوع السيارة.');
            }
        };
    }

    public function archive(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->authorize('update', $vehicle);

        $vehicle->update(['status' => 'archived']);

        return response()->json($vehicle->fresh());
    }
}
