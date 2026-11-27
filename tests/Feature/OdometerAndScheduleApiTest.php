<?php

namespace Tests\Feature;

use App\Models\MaintenanceSchedule;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OdometerAndScheduleApiTest extends TestCase
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
            'default_interval_months' => 6,
        ]);
    }

    private function createVehicle(User $owner, int $odometer = 84000): Vehicle
    {
        return Vehicle::create([
            'owner_user_id' => $owner->id,
            'chassis_no' => 'JN1AZ34E12345678X',
            'chassis_hash' => str_repeat('b', 64),
            'make' => 'Nissan',
            'model' => '370Z',
            'status' => 'active',
            'source' => 'manual',
            'current_odometer' => $odometer,
        ]);
    }

    public function test_odometer_update_raises_current_and_stores_reading(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);
        $vehicle = $this->createVehicle($owner);

        $this->postJson("/api/vehicles/{$vehicle->id}/odometer", ['km' => 84500])
            ->assertOk()
            ->assertJsonPath('current_odometer', 84500);

        $this->assertDatabaseHas('odometer_readings', ['vehicle_id' => $vehicle->id, 'km' => 84500, 'source' => 'manual']);
    }

    public function test_lower_odometer_needs_a_reason(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);
        $vehicle = $this->createVehicle($owner, 84000);

        $this->postJson("/api/vehicles/{$vehicle->id}/odometer", ['km' => 83000])
            ->assertStatus(422)
            ->assertJsonValidationErrors('km');

        $this->postJson("/api/vehicles/{$vehicle->id}/odometer", ['km' => 83000, 'confirm_reason' => 'تغيير القاطع'])
            ->assertOk();

        $this->assertSame(84000, $vehicle->fresh()->current_odometer);
    }

    public function test_user_cannot_update_odometer_of_another_vehicle(): void
    {
        $owner = User::factory()->create();
        $vehicle = $this->createVehicle($owner);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/vehicles/{$vehicle->id}/odometer", ['km' => 90000])
            ->assertForbidden();
    }

    public function test_service_record_sets_next_due_and_reading(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);
        $vehicleId = $this->postJson('/api/vehicles', [
            'chassis_no' => 'JN1AZ34E12345678X',
            'make' => 'Nissan',
            'model' => '370Z',
            'current_odometer' => 80000,
        ])->assertCreated()->json('id');

        $this->postJson("/api/vehicles/{$vehicleId}/service-records", [
            'service_type_id' => $this->oil->id,
            'service_date' => '2026-11-20',
            'odometer' => 84000,
        ])->assertCreated();

        $schedule = MaintenanceSchedule::where('vehicle_id', $vehicleId)
            ->where('service_type_id', $this->oil->id)
            ->first();
        $this->assertSame(94000, $schedule->next_due_km);
        $this->assertSame('2027-05-20', $schedule->next_due_date->toDateString());
        $this->assertDatabaseHas('odometer_readings', ['vehicle_id' => $vehicleId, 'km' => 84000, 'source' => 'service_record']);
    }

    public function test_schedules_list_marks_due_items(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);
        $vehicle = $this->createVehicle($owner, 93700);
        MaintenanceSchedule::create([
            'vehicle_id' => $vehicle->id,
            'service_type_id' => $this->oil->id,
            'interval_km' => 10000,
            'enabled' => true,
            'next_due_km' => 94000,
        ]);

        $this->getJson("/api/vehicles/{$vehicle->id}/schedules")
            ->assertOk()
            ->assertJsonPath('0.service_type_name_ar', 'زيت المحرك')
            ->assertJsonPath('0.is_due', true);
    }

    public function test_schedules_list_is_private_to_the_owner(): void
    {
        $vehicle = $this->createVehicle(User::factory()->create());
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/vehicles/{$vehicle->id}/schedules")->assertForbidden();
    }
}
