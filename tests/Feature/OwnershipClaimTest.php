<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\OwnershipClaim;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OwnershipClaimTest extends TestCase
{
    use RefreshDatabase;

    private const CHASSIS = 'JN1AZ34E12345678X';

    /** سيارة بدون مالك (كأن المالك السابق حذف حسابه). */
    private function orphanVehicle(): Vehicle
    {
        $previous = User::factory()->create();
        Sanctum::actingAs($previous);
        $id = $this->postJson('/api/vehicles', [
            'chassis_no' => self::CHASSIS, 'make' => 'Nissan', 'model' => '370Z', 'year' => 2016,
        ])->json('id');

        $vehicle = Vehicle::findOrFail($id);
        DB::table('vehicles')->where('id', $id)->update(['owner_user_id' => null]);

        return $vehicle->fresh();
    }

    private function admin(): AdminUser
    {
        return AdminUser::create([
            'name' => 'مراجع', 'email' => uniqid().'@admin.test',
            'password' => 'secret-pass-123', 'role' => 'reviewer',
        ]);
    }

    public function test_lookup_for_orphan_vehicle_shows_only_make_model_year(): void
    {
        Storage::fake('local');
        $vehicle = $this->orphanVehicle();

        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/vehicles/lookup?chassis_no='.self::CHASSIS)
            ->assertOk()
            ->assertJsonPath('make', 'Nissan')
            ->assertJsonPath('model', '370Z')
            ->assertJsonPath('year', 2016)
            ->assertJsonMissing(['chassis_no' => self::CHASSIS])
            ->assertJsonMissing(['chassis_last4'])
            ->assertJsonMissing(['service_records']);
    }

    public function test_approval_creates_ownership_and_sets_owner(): void
    {
        Storage::fake('local');
        $vehicle = $this->orphanVehicle();
        $buyer = User::factory()->create();

        Sanctum::actingAs($buyer);
        $claimId = $this->postJson("/api/vehicles/{$vehicle->id}/ownership-claims", [
            'proof' => UploadedFile::fake()->image('registration.jpg'),
        ])->assertCreated()->json('id');

        Sanctum::actingAs($this->admin());
        $this->postJson("/api/admin/claims/{$claimId}/approve")->assertOk();

        $vehicle->refresh();
        $this->assertSame($buyer->id, $vehicle->owner_user_id);
        $this->assertSame('active', $vehicle->status);
        $this->assertDatabaseHas('vehicle_ownerships', ['vehicle_id' => $vehicle->id, 'user_id' => $buyer->id]);
    }

    public function test_rejection_keeps_vehicle_orphaned_and_allows_new_claim(): void
    {
        Storage::fake('local');
        $vehicle = $this->orphanVehicle();
        $buyer = User::factory()->create();

        Sanctum::actingAs($buyer);
        $claimId = $this->postJson("/api/vehicles/{$vehicle->id}/ownership-claims", [
            'proof' => UploadedFile::fake()->image('blurry.jpg'),
        ])->json('id');

        Sanctum::actingAs($this->admin());
        $this->postJson("/api/admin/claims/{$claimId}/reject", ['note' => 'الاستمارة غير واضحة'])->assertOk();

        $this->assertNull($vehicle->fresh()->owner_user_id);

        Sanctum::actingAs($buyer);
        $this->postJson("/api/vehicles/{$vehicle->id}/ownership-claims", [
            'proof' => UploadedFile::fake()->image('clear.jpg'),
        ])->assertCreated();
    }

    public function test_claim_on_vehicle_with_owner_is_rejected(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);
        $id = $this->postJson('/api/vehicles', [
            'chassis_no' => self::CHASSIS, 'make' => 'Nissan', 'model' => '370Z',
        ])->json('id');

        Sanctum::actingAs(User::factory()->create());
        $this->postJson("/api/vehicles/{$id}/ownership-claims", [
            'proof' => UploadedFile::fake()->image('registration.jpg'),
        ])->assertStatus(422);
    }
}
