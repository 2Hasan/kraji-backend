<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\AuditLog;
use App\Models\PlateTemplate;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminVehicleManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role = 'admin'): AdminUser
    {
        return AdminUser::create([
            'name' => 'حساب', 'email' => uniqid().'@admin.test', 'password' => 'secret-pass-123', 'role' => $role,
        ]);
    }

    private function vehicle(): Vehicle
    {
        return Vehicle::create([
            'owner_user_id' => User::factory()->create()->id,
            'chassis_no' => 'JN1AZ34E12345678X',
            'chassis_hash' => Vehicle::hashChassis('JN1AZ34E12345678X'),
            'make' => 'Nissan', 'model' => '370Z', 'status' => 'active', 'source' => 'manual',
            'plate_style' => 'classic', 'plate_visible' => true,
        ]);
    }

    public function test_admin_edits_vehicle_fields_and_the_change_is_audited(): void
    {
        $vehicle = $this->vehicle();
        PlateTemplate::create([
            'code' => 'tpl-carbon', 'name' => 'نقل خاص', 'body_type' => 'نقل خاص',
            'svg_storage_key' => 'plate-templates/tpl-carbon.svg', 'status' => 'active', 'validation_errors' => [],
        ]);
        $this->actingAs($this->admin(), 'admin');

        $this->post(route('admin.vehicles.update', $vehicle), [
            'make' => 'Nissan', 'model' => '370Z', 'year' => 2016, 'nickname' => 'الحمراء',
            'plate_number' => '12345', 'plate_style' => 'tpl-carbon',
        ])->assertRedirect(route('admin.vehicles.index'));

        $vehicle->refresh();
        $this->assertSame('الحمراء', $vehicle->nickname);
        $this->assertSame('12345', $vehicle->plate_number);
        $this->assertSame('tpl-carbon', $vehicle->plate_style);
        $this->assertFalse($vehicle->plate_visible);
        $this->assertSame('JN1AZ34E12345678X', $vehicle->chassis_no);
        $this->assertDatabaseHas('audit_logs', ['action' => 'vehicle.updated', 'subject_id' => $vehicle->id]);
    }

    public function test_admin_can_use_an_active_template_and_it_sets_the_car_type(): void
    {
        $vehicle = $this->vehicle();
        $template = PlateTemplate::create([
            'code' => 'tpl-family', 'name' => 'خصوصي', 'body_type' => 'خصوصي',
            'svg_storage_key' => 'plate-templates/x.svg', 'status' => 'active', 'validation_errors' => [],
        ]);
        $this->actingAs($this->admin(), 'admin');

        $this->post(route('admin.vehicles.update', $vehicle), [
            'make' => 'Nissan', 'model' => '370Z', 'plate_style' => $template->code, 'plate_visible' => '1',
        ])->assertRedirect();

        $this->assertSame('خصوصي', $vehicle->fresh()->body_type);
    }

    public function test_invalid_template_is_rejected(): void
    {
        $vehicle = $this->vehicle();
        $this->actingAs($this->admin(), 'admin');

        $this->post(route('admin.vehicles.update', $vehicle), [
            'make' => 'Nissan', 'model' => '370Z', 'plate_style' => 'does-not-exist',
        ])->assertSessionHasErrors('plate_style');
    }

    public function test_admin_archives_and_restores_a_vehicle(): void
    {
        $vehicle = $this->vehicle();
        $this->actingAs($this->admin(), 'admin');

        $this->post(route('admin.vehicles.archive', $vehicle))->assertRedirect();
        $this->assertSame('archived', $vehicle->fresh()->status);

        $this->post(route('admin.vehicles.restore', $vehicle))->assertRedirect();
        $this->assertSame('active', $vehicle->fresh()->status);

        $this->assertSame(2, AuditLog::whereIn('action', ['vehicle.archived', 'vehicle.restored'])->count());
    }

    public function test_reviewer_cannot_edit_archive_or_restore_vehicles(): void
    {
        $vehicle = $this->vehicle();
        $this->actingAs($this->admin('reviewer'), 'admin');

        $this->get(route('admin.vehicles.edit', $vehicle))->assertForbidden();
        $this->post(route('admin.vehicles.update', $vehicle), ['make' => 'X', 'model' => 'Y', 'plate_style' => 'classic'])->assertForbidden();
        $this->post(route('admin.vehicles.archive', $vehicle))->assertForbidden();
        $this->assertSame('active', $vehicle->fresh()->status);
    }

    public function test_permanent_delete_works_only_for_archived_vehicles(): void
    {
        $vehicle = $this->vehicle();
        $this->actingAs($this->admin(), 'admin');

        $this->post(route('admin.vehicles.destroy', $vehicle), ['confirm' => 'حذف نهائي'])->assertUnprocessable();
        $this->assertNotNull(Vehicle::find($vehicle->id));
    }

    public function test_permanent_delete_needs_the_exact_confirmation_text(): void
    {
        $vehicle = $this->vehicle();
        $vehicle->update(['status' => 'archived']);
        $this->actingAs($this->admin(), 'admin');

        $this->post(route('admin.vehicles.destroy', $vehicle), ['confirm' => 'حذف'])->assertSessionHasErrors('confirm');
        $this->assertNotNull(Vehicle::find($vehicle->id));
    }

    public function test_permanent_delete_removes_vehicle_records_and_is_audited(): void
    {
        Storage::fake(config('filesystems.attachments_disk'));
        $vehicle = $this->vehicle();
        $vehicle->update(['status' => 'archived']);
        $this->actingAs($this->admin(), 'admin');

        $this->post(route('admin.vehicles.destroy', $vehicle), ['confirm' => 'حذف نهائي'])
            ->assertRedirect(route('admin.vehicles.index'));

        $this->assertNull(Vehicle::find($vehicle->id));
        $this->assertDatabaseHas('audit_logs', ['action' => 'vehicle.deleted', 'subject_id' => $vehicle->id]);
    }

    public function test_reviewer_cannot_permanently_delete(): void
    {
        $vehicle = $this->vehicle();
        $vehicle->update(['status' => 'archived']);
        $this->actingAs($this->admin('reviewer'), 'admin');

        $this->post(route('admin.vehicles.destroy', $vehicle), ['confirm' => 'حذف نهائي'])->assertForbidden();
        $this->assertNotNull(Vehicle::find($vehicle->id));
    }
}
