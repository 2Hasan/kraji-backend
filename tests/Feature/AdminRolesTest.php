<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\AuditLog;
use App\Models\ChangeRequest;
use App\Models\ServiceRecord;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminRolesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role = 'admin', ?string $suspendedAt = null): AdminUser
    {
        return AdminUser::create([
            'name' => 'حساب', 'email' => uniqid().'@admin.test', 'password' => 'secret-pass-123',
            'role' => $role, 'suspended_at' => $suspendedAt,
        ]);
    }

    public function test_reviewer_cannot_create_reviewers(): void
    {
        Sanctum::actingAs($this->admin('reviewer'));

        $this->postJson('/api/admin/reviewers', [
            'name' => 'مراجع جديد', 'email' => 'new@admin.test', 'password' => 'secret-pass-123',
        ])->assertForbidden();
    }

    public function test_admin_creates_reviewer(): void
    {
        Sanctum::actingAs($this->admin('admin'));

        $this->postJson('/api/admin/reviewers', [
            'name' => 'مراجع جديد', 'email' => 'new@admin.test', 'password' => 'secret-pass-123',
        ])->assertCreated();

        $this->assertDatabaseHas('admins', ['email' => 'new@admin.test', 'role' => 'reviewer']);
    }

    public function test_decision_is_written_to_audit_log_with_reviewer(): void
    {
        $type = ServiceType::create([
            'code' => 'engine_oil', 'name_ar' => 'زيت المحرك', 'name_en' => 'Engine oil',
            'default_interval_km' => 10000, 'default_interval_months' => 12,
        ]);
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);
        $vehicleId = $this->postJson('/api/vehicles', [
            'chassis_no' => 'JN1AZ34E12345678X', 'make' => 'Nissan', 'model' => '370Z',
        ])->json('id');
        $recordId = $this->postJson("/api/vehicles/{$vehicleId}/service-records", [
            'service_type_id' => $type->id, 'service_date' => '2026-11-26', 'odometer' => 84200,
        ])->json('id');
        $crId = $this->postJson("/api/service-records/{$recordId}/change-requests", [
            'type' => 'delete', 'reason' => 'صيانة مكررة',
        ])->json('id');

        $reviewer = $this->admin('reviewer');
        Sanctum::actingAs($reviewer);
        $this->postJson("/api/admin/requests/{$crId}/approve")->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'actor_admin_id' => $reviewer->id,
            'action' => 'change_request.approved',
            'subject_type' => ChangeRequest::class,
            'subject_id' => $crId,
        ]);
    }

    public function test_audit_log_cannot_be_deleted(): void
    {
        AuditLog::create([
            'actor_admin_id' => $this->admin()->id, 'action' => 'test',
            'subject_type' => 'x', 'subject_id' => 1, 'created_at' => now(),
        ]);

        $this->expectException(QueryException::class);
        AuditLog::query()->delete();
    }

    public function test_suspended_reviewer_cannot_log_in(): void
    {
        $reviewer = $this->admin('reviewer', now()->toDateTimeString());

        $this->postJson('/api/admin/login', ['email' => $reviewer->email, 'password' => 'secret-pass-123'])
            ->assertForbidden();
    }
}
