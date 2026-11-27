<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\OdometerRegressionException;
use App\Http\Controllers\Controller;
use App\Models\OdometerReading;
use App\Models\Vehicle;
use App\Services\OdometerGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OdometerController extends Controller
{
    /**
     * تحديث العداد بدون صيانة. يُحفظ كقراءة جديدة، ويرفع العداد الحالي إذا أكبر.
     */
    public function store(Request $request, Vehicle $vehicle, OdometerGuard $guard): JsonResponse
    {
        $this->authorize('update', $vehicle);

        $data = $request->validate([
            'km' => ['required', 'integer', 'min:0', 'max:9999999'],
            'confirm_reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $guard->check($vehicle, $data['km'], $data['confirm_reason'] ?? null);
        } catch (OdometerRegressionException $e) {
            throw ValidationException::withMessages(['km' => [$e->getMessage()]]);
        }

        OdometerReading::create([
            'vehicle_id' => $vehicle->id,
            'km' => $data['km'],
            'recorded_at' => now(),
            'source' => 'manual',
        ]);

        if ($data['km'] > $vehicle->current_odometer) {
            $vehicle->update(['current_odometer' => $data['km']]);
        }

        return response()->json($vehicle->fresh());
    }
}
