<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\User;
use App\Services\PlateTemplateValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlateTemplateTest extends TestCase
{
    use RefreshDatabase;

    private const GOOD = '<svg viewBox="0 0 300 110"><rect width="300" height="110"/><text>{{plate_number}}</text></svg>';

    private function admin(): AdminUser
    {
        return AdminUser::create([
            'name' => 'مدير', 'email' => uniqid().'@admin.test',
            'password' => 'secret-pass-123', 'role' => 'admin',
        ]);
    }

    public function test_template_without_placeholder_is_rejected(): void
    {
        $errors = (new PlateTemplateValidator)->validate('<svg><text>12345</text></svg>');

        $this->assertContains('لازم يحتوي {{plate_number}} مرة وحدة.', $errors);
    }

    public function test_template_with_script_is_rejected(): void
    {
        $svg = str_replace('</svg>', '<script>alert(1)</script></svg>', self::GOOD);

        $this->assertNotEmpty((new PlateTemplateValidator)->validate($svg));
    }

    public function test_activation_requires_no_errors(): void
    {
        Storage::fake('local');
        Sanctum::actingAs($this->admin());

        $id = $this->postJson('/api/admin/plate-templates', [
            'code' => 'bad', 'name' => 'قالب ناقص', 'body_type' => 'car',
            'svg' => UploadedFile::fake()->createWithContent('bad.svg', '<svg><text>12345</text></svg>'),
        ])->assertCreated()->json('id');

        $this->postJson("/api/admin/plate-templates/{$id}/activate")->assertStatus(422);
    }

    public function test_mobile_list_returns_only_active_templates_for_body_type(): void
    {
        Storage::fake('local');
        Sanctum::actingAs($this->admin());

        $carId = $this->postJson('/api/admin/plate-templates', [
            'name' => 'سيارة',
            'svg' => UploadedFile::fake()->createWithContent('car.svg', self::GOOD),
        ])->json('id');
        $this->postJson("/api/admin/plate-templates/{$carId}/activate")->assertOk();

        $bikeId = $this->postJson('/api/admin/plate-templates', [
            'name' => 'دراجة',
            'svg' => UploadedFile::fake()->createWithContent('bike.svg', self::GOOD),
        ])->json('id');
        $this->postJson("/api/admin/plate-templates/{$bikeId}/activate")->assertOk();

        Sanctum::actingAs(User::factory()->create());
        $names = collect($this->getJson('/api/plate-templates?body_type=سيارة')->assertOk()->json())->pluck('name');

        $this->assertSame(['سيارة'], $names->all());
    }
}
