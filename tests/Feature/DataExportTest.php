<?php

namespace Tests\Feature;

use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DataExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_contains_vehicles_and_records(): void
    {
        $type = ServiceType::create([
            'code' => 'engine_oil', 'name_ar' => 'زيت المحرك', 'name_en' => 'Engine oil',
            'default_interval_km' => 10000, 'default_interval_months' => 12,
        ]);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        foreach (['JN1AZ34E12345678X', 'WBA8E9C50JK123456'] as $chassis) {
            $vehicleId = $this->postJson('/api/vehicles', [
                'chassis_no' => $chassis, 'make' => 'Nissan', 'model' => '370Z',
            ])->json('id');
            $this->postJson("/api/vehicles/{$vehicleId}/service-records", [
                'service_type_id' => $type->id, 'service_date' => '2026-11-26', 'odometer' => 84200,
            ])->assertCreated();
        }

        $this->getJson('/api/account/export')
            ->assertOk()
            ->assertJsonCount(2, 'vehicles')
            ->assertJsonCount(2, 'service_records');
    }

    public function test_export_shows_only_last_four_chassis_digits(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/vehicles', [
            'chassis_no' => 'JN1AZ34E12345678X', 'make' => 'Nissan', 'model' => '370Z',
        ])->assertCreated();

        $body = $this->getJson('/api/account/export')->assertOk()->getContent();

        $this->assertStringNotContainsString('JN1AZ34E12345678X', $body);
        $this->assertStringContainsString('678X', $body);
    }
}
