<?php

namespace Tests\Unit;

use App\Exceptions\OdometerRegressionException;
use App\Models\Vehicle;
use App\Services\OdometerGuard;
use PHPUnit\Framework\TestCase;

class OdometerGuardTest extends TestCase
{
    public function test_lower_odometer_without_reason_throws(): void
    {
        $vehicle = new Vehicle(['current_odometer' => 84200]);

        $this->expectException(OdometerRegressionException::class);
        (new OdometerGuard())->check($vehicle, 84199, null);
    }

    public function test_lower_odometer_with_reason_passes(): void
    {
        $vehicle = new Vehicle(['current_odometer' => 84200]);

        (new OdometerGuard())->check($vehicle, 84199, 'خطأ في الكتابة السابقة');
        $this->assertTrue(true);
    }
}
