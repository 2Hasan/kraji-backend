<?php

namespace Tests\Feature;

use App\Models\ServiceType;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VehicleTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_types_list_is_available_to_signed_in_users(): void
    {
        ServiceType::create([
            'code' => 'engine_oil', 'name_ar' => 'زيت المحرك', 'name_en' => 'Engine oil',
            'default_interval_km' => 10000, 'default_interval_months' => 12,
        ]);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/service-types')->assertOk()->assertJsonPath('0.code', 'engine_oil');
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'chassis_no' => 'JN1AZ34E12345678X',
            'make' => 'Nissan',
            'model' => '370Z',
            'year' => 2016,
            'current_odometer' => 84200,
        ], $overrides);
    }

    public function test_duplicate_chassis_for_active_vehicle_is_rejected_generically(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);
        $this->postJson('/api/vehicles', $this->payload())->assertCreated();

        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/vehicles', $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('errors.chassis_no.0', 'هالشاصي مسجّل مسبقاً.')
            ->assertJsonMissing(['email' => $owner->email]);
    }

    public function test_chassis_is_encrypted_at_rest(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/vehicles', $this->payload())->assertCreated();

        $raw = DB::table('vehicles')->value('chassis_no');
        $this->assertNotSame('JN1AZ34E12345678X', $raw);
    }

    public function test_response_exposes_only_last_four_chassis_digits(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $id = $this->postJson('/api/vehicles', $this->payload())->json('id');

        $this->getJson("/api/vehicles/{$id}")
            ->assertOk()
            ->assertJsonPath('chassis_last4', '678X')
            ->assertJsonMissing(['chassis_no' => 'JN1AZ34E12345678X']);
    }

    public function test_vehicle_cannot_be_deleted_and_archive_sets_status(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $id = $this->postJson('/api/vehicles', $this->payload())->json('id');

        $this->deleteJson("/api/vehicles/{$id}")->assertStatus(405);

        $this->postJson("/api/vehicles/{$id}/archive")->assertOk();
        $this->assertSame('archived', Vehicle::find($id)->status);
    }

    public function test_other_users_cannot_read_vehicle(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $id = $this->postJson('/api/vehicles', $this->payload())->json('id');

        Sanctum::actingAs(User::factory()->create());
        $this->getJson("/api/vehicles/{$id}")->assertForbidden();
    }
}
