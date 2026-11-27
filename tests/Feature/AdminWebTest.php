<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\AuditLog;
use App\Models\ChangeRequest;
use App\Models\OwnershipClaim;
use App\Models\PlateTemplate;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminWebTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role = 'admin', ?string $suspendedAt = null): AdminUser
    {
        return AdminUser::create([
            'name' => 'حساب', 'email' => uniqid().'@admin.test', 'password' => 'secret-pass-123',
            'role' => $role, 'suspended_at' => $suspendedAt,
        ]);
    }

    /** يرجع ID طلب تعديل معلّق عبر الـ API (نفس مسار المستخدم الحقيقي). */
    private function pendingEditRequest(): ChangeRequest
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
            'type' => 'edit', 'reason' => 'صيانة مكررة', 'payload' => ['odometer' => 84000],
        ])->json('id');

        return ChangeRequest::findOrFail($crId);
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_admin_logs_in_and_sees_dashboard(): void
    {
        $admin = $this->admin();

        $this->post(route('admin.login'), ['email' => $admin->email, 'password' => 'secret-pass-123'])
            ->assertRedirect(route('admin.dashboard'));

        $this->get('/admin')->assertOk()->assertSee('الرئيسية');
    }

    public function test_wrong_password_is_rejected(): void
    {
        $admin = $this->admin();

        $this->post(route('admin.login'), ['email' => $admin->email, 'password' => 'wrong-password-1'])
            ->assertSessionHasErrors('email');

        $this->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_suspended_admin_cannot_log_in(): void
    {
        $admin = $this->admin('admin', now()->toDateTimeString());

        $this->post(route('admin.login'), ['email' => $admin->email, 'password' => 'secret-pass-123'])
            ->assertSessionHasErrors('email');

        $this->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_reviewer_cannot_open_staff_pages(): void
    {
        $this->actingAs($this->admin('reviewer'), 'admin');

        $this->get(route('admin.staff.index'))->assertForbidden();
        $this->get(route('admin.requests.index'))->assertOk();
    }

    public function test_admin_approves_change_request_and_audit_is_written(): void
    {
        $cr = $this->pendingEditRequest();
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');

        $this->post(route('admin.requests.approve', $cr), ['note' => 'تمام'])
            ->assertRedirect(route('admin.requests.index'));

        $this->assertSame('approved', $cr->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['actor_admin_id' => $admin->id, 'action' => 'change_request.approved']);
    }

    public function test_reject_needs_a_reason(): void
    {
        $cr = $this->pendingEditRequest();
        $this->actingAs($this->admin(), 'admin');

        $this->post(route('admin.requests.reject', $cr), ['note' => ''])
            ->assertSessionHasErrors('note');

        $this->assertSame('pending', $cr->fresh()->status);
    }

    public function test_approving_ownership_claim_transfers_vehicle(): void
    {
        $vehicle = Vehicle::create([
            'chassis_no' => 'JN1AZ34E12345678X', 'make' => 'Nissan', 'model' => '370Z', 'status' => 'active',
        ]);
        $vehicle->update(['owner_user_id' => null]);
        $claimant = User::factory()->create();
        $claim = OwnershipClaim::create([
            'vehicle_id' => $vehicle->id, 'claimant_user_id' => $claimant->id,
            'proof_storage_key' => 'ownership-proofs/x.pdf', 'status' => 'pending',
        ]);
        $this->actingAs($this->admin(), 'admin');

        $this->post(route('admin.claims.approve', $claim))->assertRedirect(route('admin.claims.index'));

        $this->assertSame($claimant->id, $vehicle->fresh()->owner_user_id);
        $this->assertSame('approved', $claim->fresh()->status);
    }

    public function test_proof_file_is_served_only_to_logged_in_admin(): void
    {
        $disk = config('filesystems.attachments_disk');
        Storage::fake($disk);
        Storage::disk($disk)->put('ownership-proofs/1/proof.pdf', 'pdf-bytes');

        $vehicle = Vehicle::create([
            'chassis_no' => 'JN1AZ34E12345678X', 'make' => 'Nissan', 'model' => '370Z', 'status' => 'active',
        ]);
        $vehicle->update(['owner_user_id' => null]);
        $claim = OwnershipClaim::create([
            'vehicle_id' => $vehicle->id, 'claimant_user_id' => User::factory()->create()->id,
            'proof_storage_key' => 'ownership-proofs/1/proof.pdf', 'status' => 'pending',
        ]);

        $this->get(route('admin.claims.proof', $claim))->assertRedirect(route('admin.login'));

        $this->actingAs($this->admin(), 'admin');
        $this->get(route('admin.claims.proof', $claim))->assertOk();
    }

    public function test_template_with_errors_cannot_be_activated(): void
    {
        $template = PlateTemplate::create([
            'code' => 'bad', 'name' => 'قالب', 'body_type' => 'coupe',
            'svg_storage_key' => 'plate-templates/bad.svg', 'status' => 'draft',
            'validation_errors' => ['لازم يحتوي {{plate_number}} مرة وحدة.'],
        ]);
        $this->actingAs($this->admin(), 'admin');

        $this->post(route('admin.plate-templates.activate', $template))->assertSessionHasErrors('activate');

        $this->assertSame('draft', $template->fresh()->status);
    }

    public function test_admin_uploads_template_as_draft_and_audit_is_untouched(): void
    {
        $disk = config('filesystems.attachments_disk');
        Storage::fake($disk);
        $this->actingAs($this->admin(), 'admin');

        $svg = UploadedFile::fake()->createWithContent('t.svg', '<svg><text>{{plate_number}}</text></svg>');

        $this->post(route('admin.plate-templates.store'), [
            'name' => 'نقل خاص', 'svg' => $svg,
        ])->assertRedirect(route('admin.plate-templates.index'));

        $this->assertDatabaseHas('plate_templates', ['name' => 'نقل خاص', 'body_type' => 'نقل خاص', 'status' => 'draft']);
        $this->assertSame(0, AuditLog::count());
    }

    public function test_admin_replaces_template_file_with_fixed_svg_and_reruns_validation(): void
    {
        $disk = config('filesystems.attachments_disk');
        Storage::fake($disk);
        Storage::disk($disk)->put('plate-templates/PLC.svg', '<svg>POLICE {{plate_number}}</svg>');
        $template = PlateTemplate::create([
            'code' => 'PLC', 'name' => 'PLC', 'body_type' => 'PLC',
            'svg_storage_key' => 'plate-templates/PLC.svg', 'status' => 'draft',
            'validation_errors' => ['القالب يحتوي كلمة رسمية ممنوعة: POLICE'],
        ]);
        $this->actingAs($this->admin(), 'admin');

        $fixed = UploadedFile::fake()->createWithContent('fixed.svg', '<svg><text>{{plate_number}}</text></svg>');
        $this->post(route('admin.plate-templates.replace-svg', $template), ['svg' => $fixed])
            ->assertRedirect(route('admin.plate-templates.index'));

        $template->refresh();
        $this->assertSame([], $template->validation_errors);
        $this->assertSame('draft', $template->status);
        $this->assertSame('<svg><text>{{plate_number}}</text></svg>', Storage::disk($disk)->get('plate-templates/PLC.svg'));
        $this->post(route('admin.plate-templates.activate', $template))->assertRedirect(route('admin.plate-templates.index'));
        $this->assertSame('active', $template->fresh()->status);
    }

    public function test_replacing_active_template_with_forbidden_svg_returns_it_to_draft(): void
    {
        $disk = config('filesystems.attachments_disk');
        Storage::fake($disk);
        $template = PlateTemplate::create([
            'code' => 'classic', 'name' => 'كلاسيك', 'body_type' => 'sedan',
            'svg_storage_key' => 'plate-templates/classic.svg', 'status' => 'active',
            'validation_errors' => [],
        ]);
        $this->actingAs($this->admin(), 'admin');

        $bad = UploadedFile::fake()->createWithContent('bad.svg', '<svg>POLICE {{plate_number}}</svg>');
        $this->post(route('admin.plate-templates.replace-svg', $template), ['svg' => $bad])
            ->assertRedirect(route('admin.plate-templates.index'));

        $template->refresh();
        $this->assertSame('draft', $template->status);
        $this->assertContains('القالب يحتوي كلمة رسمية ممنوعة: POLICE', $template->validation_errors);
        $this->post(route('admin.plate-templates.activate', $template))->assertSessionHasErrors('activate');
    }

    public function test_logout_ends_session(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));
        $this->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_signed_in_admin_opening_login_page_goes_to_dashboard(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->get(route('admin.login'))->assertRedirect(route('admin.dashboard'));
    }

    public function test_audit_entries_show_arabic_labels_not_class_names(): void
    {
        $this->actingAs($this->admin(), 'admin');
        AuditLog::create([
            'actor_admin_id' => $this->admin()->id, 'action' => 'ownership_claim.approved',
            'subject_type' => OwnershipClaim::class, 'subject_id' => 7, 'created_at' => now(),
        ]);

        $this->get(route('admin.staff.index', ['tab' => 'audit']))
            ->assertSee('موافقة على ملكية')
            ->assertSee('طلب ملكية #7')
            ->assertDontSee('App\Models');
    }
}
