<?php

namespace Tests\Feature;

use App\Models\MaintenanceSchedule;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MaintenanceScheduleTest extends TestCase
{
    use RefreshDatabase;

    private ServiceType $oil;

    protected function setUp(): void
    {
        parent::setUp();

        $this->oil = ServiceType::create([
            'code' => 'engine_oil',
            'name_ar' => 'زيت المحرك',
            'name_en' => 'Engine oil',
            'default_interval_km' => 10000,
            'default_interval_months' => 12,
        ]);
    }

    private function createVehicle(): int
    {
        return $this->postJson('/api/vehicles', [
            'chassis_no' => 'JN1AZ34E12345678X',
            'make' => 'Nissan',
            'model' => '370Z',
        ])->assertCreated()->json('id');
    }

    public function test_new_vehicle_gets_default_schedules_copied(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $vehicleId = $this->createVehicle();

        $this->assertDatabaseHas('maintenance_schedules', [
            'vehicle_id' => $vehicleId,
            'service_type_id' => $this->oil->id,
            'interval_km' => 10000,
            'interval_months' => 12,
            'enabled' => true,
        ]);
    }

    public function test_changing_template_does_not_change_existing_schedules(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $vehicleId = $this->createVehicle();

        $this->oil->update(['default_interval_km' => 5000]);

        $this->assertDatabaseHas('maintenance_schedules', [
            'vehicle_id' => $vehicleId,
            'interval_km' => 10000,
        ]);
    }

    public function test_patch_changes_only_that_schedule(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $vehicleId = $this->createVehicle();
        $schedule = MaintenanceSchedule::where('vehicle_id', $vehicleId)->firstOrFail();

        $this->patchJson("/api/schedules/{$schedule->id}", ['interval_km' => 8000])
            ->assertOk()
            ->assertJsonPath('interval_km', 8000);

        $other = MaintenanceSchedule::count();
        $this->assertSame(1, $other);
    }

    public function test_user_cannot_patch_someone_elses_schedule(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $vehicleId = $this->createVehicle();
        $schedule = MaintenanceSchedule::where('vehicle_id', $vehicleId)->firstOrFail();

        Sanctum::actingAs(User::factory()->create());
        $this->patchJson("/api/schedules/{$schedule->id}", ['interval_km' => 1])
            ->assertForbidden();
    }
}
