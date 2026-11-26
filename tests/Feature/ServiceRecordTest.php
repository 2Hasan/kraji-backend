<?php

namespace Tests\Feature;

use App\Models\ServiceRecord;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ServiceRecordTest extends TestCase
{
    use RefreshDatabase;

    private ServiceType $oil;

    protected function setUp(): void
    {
        parent::setUp();
        $this->oil = ServiceType::create([
            'code' => 'engine_oil', 'name_ar' => 'زيت المحرك', 'name_en' => 'Engine oil',
            'default_interval_km' => 10000, 'default_interval_months' => 12,
        ]);
    }

    private function vehicleFor(User $user, int $odometer = 84200): Vehicle
    {
        Sanctum::actingAs($user);
        $id = $this->postJson('/api/vehicles', [
            'chassis_no' => 'JN1AZ34E12345678X', 'make' => 'Nissan', 'model' => '370Z',
            'current_odometer' => $odometer,
        ])->assertCreated()->json('id');

        return Vehicle::findOrFail($id);
    }

    private function recordPayload(array $overrides = []): array
    {
        return array_merge([
            'service_type_id' => $this->oil->id,
            'service_date' => '2026-11-26',
            'odometer' => 84200,
            'cost' => 35.000,
            'workshop_name' => 'ورشة الأمان',
        ], $overrides);
    }

    public function test_lower_odometer_without_reason_is_rejected(): void
    {
        $user = User::factory()->create();
        $vehicle = $this->vehicleFor($user, 84200);

        $this->postJson("/api/vehicles/{$vehicle->id}/service-records", $this->recordPayload(['odometer' => 84000]))
            ->assertStatus(422);
    }

    public function test_creating_record_updates_current_odometer(): void
    {
        $user = User::factory()->create();
        $vehicle = $this->vehicleFor($user, 84200);

        $this->postJson("/api/vehicles/{$vehicle->id}/service-records", $this->recordPayload(['odometer' => 85000]))
            ->assertCreated();

        $this->assertSame(85000, $vehicle->fresh()->current_odometer);
    }

    public function test_attachment_link_expires_after_15_minutes(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $vehicle = $this->vehicleFor($user);
        $recordId = $this->postJson("/api/vehicles/{$vehicle->id}/service-records", $this->recordPayload())->json('id');

        $this->postJson("/api/service-records/{$recordId}/attachments", [
            'file' => UploadedFile::fake()->image('invoice.jpg'),
        ])->assertCreated();

        $url = $this->getJson("/api/service-records/{$recordId}")->json('attachments.0.url');
        $this->assertNotNull($url);

        Carbon::setTestNow(now()->addMinutes(16));
        $this->get($url)->assertForbidden();
        Carbon::setTestNow();
    }

    public function test_record_cannot_be_patched_or_deleted(): void
    {
        $user = User::factory()->create();
        $vehicle = $this->vehicleFor($user);
        $recordId = $this->postJson("/api/vehicles/{$vehicle->id}/service-records", $this->recordPayload())->json('id');

        $this->patchJson("/api/service-records/{$recordId}", ['odometer' => 1])->assertStatus(405);
        $this->deleteJson("/api/service-records/{$recordId}")->assertStatus(405);
    }
}
