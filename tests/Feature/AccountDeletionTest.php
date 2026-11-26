<?php

namespace Tests\Feature;

use App\Models\OwnershipClaim;
use App\Models\ServiceRecord;
use App\Models\ServiceRecordAttachment;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Vehicle $vehicle;

    private ServiceRecord $record;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $type = ServiceType::create([
            'code' => 'engine_oil', 'name_ar' => 'زيت المحرك', 'name_en' => 'Engine oil',
            'default_interval_km' => 10000, 'default_interval_months' => 12,
        ]);

        $this->user = User::factory()->create(['password' => 'secret-pass-123']);
        Sanctum::actingAs($this->user);

        $vehicleId = $this->postJson('/api/vehicles', [
            'chassis_no' => 'JN1AZ34E12345678X', 'make' => 'Nissan', 'model' => '370Z',
        ])->json('id');
        $this->vehicle = Vehicle::findOrFail($vehicleId);

        $recordId = $this->postJson("/api/vehicles/{$vehicleId}/service-records", [
            'service_type_id' => $type->id, 'service_date' => '2026-11-26', 'odometer' => 84200,
        ])->json('id');
        $this->record = ServiceRecord::findOrFail($recordId);

        $this->postJson("/api/service-records/{$recordId}/attachments", [
            'file' => UploadedFile::fake()->image('invoice.jpg'),
        ])->assertCreated();
    }

    public function test_deletion_removes_personal_data_and_keeps_vehicle(): void
    {
        $this->deleteJson('/api/account', ['password' => 'secret-pass-123'])->assertOk();

        $this->assertDatabaseHas('vehicles', ['id' => $this->vehicle->id]);
        $this->assertNull($this->vehicle->fresh()->owner_user_id);
        $this->assertNull(User::withTrashed()->find($this->user->id)->email);
        $this->assertNull(User::withTrashed()->find($this->user->id)->name);
        $this->assertNotNull(User::withTrashed()->find($this->user->id)->deleted_at);
    }

    public function test_invoice_image_is_blurred_on_deletion(): void
    {
        $attachment = ServiceRecordAttachment::firstOrFail();
        $originalBytes = Storage::disk('local')->get($attachment->storage_key);

        $this->deleteJson('/api/account', ['password' => 'secret-pass-123'])->assertOk();

        $this->assertNotNull($attachment->fresh()->redacted_at);
        $this->assertTrue(Storage::disk('local')->exists($attachment->storage_key));
        $this->assertNotSame($originalBytes, Storage::disk('local')->get($attachment->storage_key));
    }

    public function test_records_are_kept_and_show_deleted_creator(): void
    {
        $this->deleteJson('/api/account', ['password' => 'secret-pass-123'])->assertOk();

        $this->assertDatabaseHas('service_records', ['id' => $this->record->id]);
        $this->assertNull(ServiceRecord::find($this->record->id)->created_by_user_id);
    }

    public function test_pending_ownership_claim_is_cancelled(): void
    {
        $claim = OwnershipClaim::create([
            'vehicle_id' => $this->vehicle->id,
            'claimant_user_id' => $this->user->id,
            'proof_storage_key' => 'ownership-proofs/x.jpg',
            'status' => 'pending',
        ]);

        $this->deleteJson('/api/account', ['password' => 'secret-pass-123'])->assertOk();

        $fresh = OwnershipClaim::find($claim->id);
        $this->assertSame('cancelled', $fresh->status);
        $this->assertNull($fresh->claimant_user_id);
    }

    public function test_deleted_account_cannot_log_in(): void
    {
        $email = $this->user->email;
        $this->deleteJson('/api/account', ['password' => 'secret-pass-123'])->assertOk();

        $this->postJson('/api/auth/login', ['email' => $email, 'password' => 'secret-pass-123'])
            ->assertStatus(422);
    }

    public function test_invoice_is_marked_redacted_but_kept(): void
    {
        $attachment = ServiceRecordAttachment::firstOrFail();

        $this->deleteJson('/api/account', ['password' => 'secret-pass-123'])->assertOk();

        $fresh = ServiceRecordAttachment::find($attachment->id);
        $this->assertNotNull($fresh->redacted_at);
        Storage::disk('local')->assertExists($fresh->storage_key);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->deleteJson('/api/account', ['password' => 'wrong-pass'])->assertStatus(422);
        $this->assertNotNull(User::find($this->user->id));
    }
}
