<?php

namespace App\Services;

use App\Exceptions\OdometerRegressionException;
use App\Models\Vehicle;

class OdometerGuard
{
    /**
     * العداد المدخل أقل من آخر قراءة معروفة يحتاج سبب، وإلا يُرفض.
     */
    public function check(Vehicle $vehicle, int $km, ?string $confirmReason): void
    {
        if ($km < (int) $vehicle->current_odometer && blank($confirmReason)) {
            throw new OdometerRegressionException('العداد أقل من آخر قراءة. اكتب سبب التأكيد.');
        }
    }
}
