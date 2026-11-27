<?php

namespace Tests\Feature;

use App\Models\PlateTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlateStyleApiTest extends TestCase
{
    use RefreshDatabase;

    private const SVG = '<svg><text>{{plate_number}}</text></svg>';

    private function activeTemplate(string $code, string $bodyType, string $status = 'active'): PlateTemplate
    {
        $key = 'plate-templates/'.$code.'.svg';
        Storage::disk(config('filesystems.attachments_disk'))->put($key, self::SVG);

        return PlateTemplate::create([
            'code' => $code, 'name' => $code, 'body_type' => $bodyType,
            'svg_storage_key' => $key, 'status' => $status, 'validation_errors' => [],
        ]);
    }

    public function test_vehicle_accepts_template_of_its_own_body_type(): void
    {
        Storage::fake(config('filesystems.attachments_disk'));
        $this->activeTemplate('plc-classic', 'PLC');
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/vehicles', [
            'chassis_no' => 'JN1AZ34E12345678X', 'make' => 'Nissan', 'model' => '370Z',
            'body_type' => 'PLC', 'plate_style' => 'plc-classic',
        ])->assertCreated()
            ->assertJsonPath('body_type', 'PLC')
            ->assertJsonPath('plate_style', 'plc-classic');
    }

    public function test_template_sets_the_vehicle_car_type(): void
    {
        Storage::fake(config('filesystems.attachments_disk'));
        $this->activeTemplate('sedan-1', 'نقل خاص');
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/vehicles', [
            'chassis_no' => 'JN1AZ34E12345678X', 'make' => 'Nissan', 'model' => '370Z',
            'plate_style' => 'sedan-1',
        ])->assertCreated()->assertJsonPath('body_type', 'نقل خاص');
    }

    public function test_draft_template_cannot_be_used_for_a_vehicle(): void
    {
        Storage::fake(config('filesystems.attachments_disk'));
        $this->activeTemplate('plc-draft', 'PLC', 'draft');
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/vehicles', [
            'chassis_no' => 'JN1AZ34E12345678X', 'make' => 'Nissan', 'model' => '370Z',
            'body_type' => 'PLC', 'plate_style' => 'plc-draft',
        ])->assertUnprocessable()->assertJsonValidationErrors('plate_style');
    }

    public function test_legacy_plate_style_still_accepted(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/vehicles', [
            'chassis_no' => 'JN1AZ34E12345678X', 'make' => 'Nissan', 'model' => '370Z',
            'plate_style' => 'carbon',
        ])->assertCreated()->assertJsonPath('plate_style', 'carbon');
    }

    public function test_svg_is_served_only_for_active_templates(): void
    {
        Storage::fake(config('filesystems.attachments_disk'));
        $active = $this->activeTemplate('plc-classic', 'PLC');
        $draft = $this->activeTemplate('plc-draft', 'PLC', 'draft');
        Sanctum::actingAs(User::factory()->create());

        $this->get("/api/plate-templates/{$active->code}/svg")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');
        $this->get("/api/plate-templates/{$draft->code}/svg")->assertNotFound();
    }

    public function test_template_list_without_filter_returns_all_active(): void
    {
        Storage::fake(config('filesystems.attachments_disk'));
        $this->activeTemplate('plc-classic', 'PLC');
        $this->activeTemplate('sedan-1', 'sedan');
        $this->activeTemplate('plc-draft', 'PLC', 'draft');
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/plate-templates')->assertOk()->assertJsonCount(2);
    }
}
