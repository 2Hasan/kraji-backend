<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\ChangeRequest;
use App\Models\ServiceRecord;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChangeRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private ServiceRecord $record;

    protected function setUp(): void
    {
        parent::setUp();

        $type = ServiceType::create([
            'code' => 'engine_oil', 'name_ar' => 'زيت المحرك', 'name_en' => 'Engine oil',
            'default_interval_km' => 10000, 'default_interval_months' => 12,
        ]);

        $this->owner = User::factory()->create();
        Sanctum::actingAs($this->owner);
        $vehicleId = $this->postJson('/api/vehicles', [
            'chassis_no' => 'JN1AZ34E12345678X', 'make' => 'Nissan', 'model' => '370Z',
        ])->json('id');

        $recordId = $this->postJson("/api/vehicles/{$vehicleId}/service-records", [
            'service_type_id' => $type->id, 'service_date' => '2026-11-26',
            'odometer' => 84200, 'cost' => 35.000,
        ])->json('id');

        $this->record = ServiceRecord::findOrFail($recordId);
    }

    private function admin(string $role = 'reviewer'): AdminUser
    {
        return AdminUser::create([
            'name' => 'مراجع', 'email' => uniqid().'@admin.test',
            'password' => 'secret-pass-123', 'role' => $role,
        ]);
    }

    public function test_reason_is_required(): void
    {
        Sanctum::actingAs($this->owner);

        $this->postJson("/api/service-records/{$this->record->id}/change-requests", [
            'type' => 'delete',
        ])->assertStatus(422);
    }

    public function test_approved_delete_hides_record_but_keeps_it(): void
    {
        Sanctum::actingAs($this->owner);
        $crId = $this->postJson("/api/service-records/{$this->record->id}/change-requests", [
            'type' => 'delete', 'reason' => 'سجّلت الصيانة بتاريخ خطأ',
        ])->assertCreated()->json('id');

        Sanctum::actingAs($this->admin());
        $this->postJson("/api/admin/requests/{$crId}/approve")->assertOk();

        $this->assertDatabaseHas('service_records', ['id' => $this->record->id, 'status' => 'voided']);

        Sanctum::actingAs($this->owner);
        $this->getJson("/api/service-records/{$this->record->id}")
            ->assertOk()
            ->assertJsonPath('status', 'voided')
            ->assertJsonPath('void_reason', 'سجّلت الصيانة بتاريخ خطأ');
    }

    public function test_reject_requires_note(): void
    {
        Sanctum::actingAs($this->owner);
        $crId = $this->postJson("/api/service-records/{$this->record->id}/change-requests", [
            'type' => 'delete', 'reason' => 'صيانة مكررة',
        ])->json('id');

        Sanctum::actingAs($this->admin());
        $this->postJson("/api/admin/requests/{$crId}/reject", [])->assertStatus(422);
    }

    public function test_decision_is_attributed_to_reviewer(): void
    {
        Sanctum::actingAs($this->owner);
        $crId = $this->postJson("/api/service-records/{$this->record->id}/change-requests", [
            'type' => 'edit', 'reason' => 'العداد غلط', 'payload' => ['odometer' => 84300],
        ])->json('id');

        $reviewer = $this->admin();
        Sanctum::actingAs($reviewer);
        $this->postJson("/api/admin/requests/{$crId}/approve")->assertOk();

        $this->assertSame($reviewer->id, ChangeRequest::find($crId)->reviewed_by_admin_id);
        $this->assertSame(84300, ServiceRecord::find($this->record->id)->odometer);
    }

    public function test_owner_cannot_review(): void
    {
        Sanctum::actingAs($this->owner);
        $crId = $this->postJson("/api/service-records/{$this->record->id}/change-requests", [
            'type' => 'delete', 'reason' => 'صيانة مكررة',
        ])->json('id');

        $this->postJson("/api/admin/requests/{$crId}/approve")->assertForbidden();
    }
}
