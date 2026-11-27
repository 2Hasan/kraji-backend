<?php

namespace Tests\Feature;

use App\Models\OwnershipClaim;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileScreensApiTest extends TestCase
{
    use RefreshDatabase;

    private function createVehicleFor(User $owner, string $chassis = 'JN1AZ34E12345678X'): Vehicle
    {
        return Vehicle::create([
            'owner_user_id' => $owner->id,
            'chassis_no' => $chassis,
            'chassis_hash' => Vehicle::hashChassis($chassis),
            'make' => 'Nissan',
            'model' => '370Z',
            'status' => 'active',
            'source' => 'manual',
            'current_odometer' => 84000,
        ]);
    }

    public function test_vehicle_can_be_created_with_plate_style_and_visibility(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/vehicles', [
            'chassis_no' => 'JN1AZ34E12345678X',
            'make' => 'Nissan',
            'model' => '370Z',
            'plate_number' => '12345',
            'plate_style' => 'blue',
            'plate_visible' => false,
        ])->assertCreated()
            ->assertJsonPath('plate_style', 'blue')
            ->assertJsonPath('plate_visible', false);
    }

    public function test_vehicle_list_flags_odometer_update_when_no_reading_yet(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);
        $this->createVehicleFor($owner);

        $this->getJson('/api/vehicles')
            ->assertOk()
            ->assertJsonPath('0.needs_odometer_update', true);
    }

    public function test_change_requests_for_a_vehicle_show_only_my_requests(): void
    {
        ServiceType::create([
            'code' => 'engine_oil', 'name_ar' => 'زيت المحرك', 'name_en' => 'Engine oil',
            'default_interval_km' => 10000, 'default_interval_months' => 12,
        ]);
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);
        $vehicleId = $this->postJson('/api/vehicles', [
            'chassis_no' => 'JN1AZ34E12345678X', 'make' => 'Nissan', 'model' => '370Z',
        ])->assertCreated()->json('id');

        $recordId = $this->postJson("/api/vehicles/{$vehicleId}/service-records", [
            'service_type_id' => ServiceType::first()->id,
            'service_date' => '2026-11-20',
            'odometer' => 84000,
        ])->assertCreated()->json('id');

        $this->postJson("/api/service-records/{$recordId}/change-requests", [
            'type' => 'delete',
            'reason' => 'سجّلتها مرتين',
        ])->assertCreated();

        $this->getJson("/api/vehicles/{$vehicleId}/change-requests")
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.type', 'delete')
            ->assertJsonPath('0.status', 'pending');

        Sanctum::actingAs(User::factory()->create());
        $this->getJson("/api/vehicles/{$vehicleId}/change-requests")->assertForbidden();
    }

    public function test_my_ownership_claims_list_only_my_claims(): void
    {
        $owner = User::factory()->create();
        $vehicle = $this->createVehicleFor($owner);
        $buyer = User::factory()->create();
        OwnershipClaim::create([
            'vehicle_id' => $vehicle->id,
            'claimant_user_id' => $buyer->id,
            'proof_storage_key' => 'ownership-proofs/1/x.pdf',
            'status' => 'rejected',
            'review_note' => 'الاستمارة غير واضحة',
        ]);

        Sanctum::actingAs($buyer);
        $this->getJson('/api/ownership-claims')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.status', 'rejected')
            ->assertJsonPath('0.review_note', 'الاستمارة غير واضحة');

        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/ownership-claims')->assertOk()->assertJsonCount(0);
    }

    public function test_service_types_come_with_arabic_names(): void
    {
        ServiceType::create([
            'code' => 'engine_oil', 'name_ar' => 'زيت المحرك', 'name_en' => 'Engine oil',
            'default_interval_km' => 10000, 'default_interval_months' => 12,
        ]);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/service-types')
            ->assertOk()
            ->assertJsonPath('0.name_ar', 'زيت المحرك');
    }

    public function test_notification_settings_default_on_and_can_be_turned_off(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/account/notification-settings')
            ->assertOk()
            ->assertJson(['reminder_km' => true, 'odometer_prompt' => true]);

        $this->patchJson('/api/account/notification-settings', ['reminder_km' => false])
            ->assertOk()
            ->assertJsonPath('reminder_km', false)
            ->assertJsonPath('reminder_days', true);
    }
}
