<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\PasswordResetCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_sends_code_for_registered_email(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'owner@example.com']);

        $this->postJson('/api/auth/forgot-password', ['email' => 'owner@example.com'])
            ->assertOk();

        Notification::assertSentOnDemand(PasswordResetCodeNotification::class);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'owner@example.com']);
    }

    public function test_forgot_answers_the_same_for_unknown_email_without_sending(): void
    {
        Notification::fake();

        $this->postJson('/api/auth/forgot-password', ['email' => 'nobody@example.com'])
            ->assertOk()
            ->assertJsonStructure(['message']);

        Notification::assertNothingSent();
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'nobody@example.com']);
    }

    public function test_reset_with_correct_code_changes_password_and_revokes_tokens(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com', 'password' => 'old-pass-123']);
        DB::table('password_reset_tokens')->insert([
            'email' => 'owner@example.com',
            'token' => Hash::make('482913'),
            'created_at' => now(),
        ]);
        $user->createToken('mobile');

        $this->postJson('/api/auth/reset-password', [
            'email' => 'owner@example.com',
            'code' => '482913',
            'password' => 'new-pass-456',
        ])->assertOk();

        $user->refresh();
        $this->assertTrue(Hash::check('new-pass-456', $user->password));
        $this->assertCount(0, $user->tokens);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'owner@example.com']);
    }

    public function test_reset_with_wrong_code_is_rejected(): void
    {
        User::factory()->create(['email' => 'owner@example.com']);
        DB::table('password_reset_tokens')->insert([
            'email' => 'owner@example.com',
            'token' => Hash::make('482913'),
            'created_at' => now(),
        ]);

        $this->postJson('/api/auth/reset-password', [
            'email' => 'owner@example.com',
            'code' => '000000',
            'password' => 'new-pass-456',
        ])->assertStatus(422)->assertJsonValidationErrors('code');
    }

    public function test_reset_with_expired_code_is_rejected(): void
    {
        User::factory()->create(['email' => 'owner@example.com']);
        DB::table('password_reset_tokens')->insert([
            'email' => 'owner@example.com',
            'token' => Hash::make('482913'),
            'created_at' => Carbon::now()->subMinutes(16),
        ]);

        $this->postJson('/api/auth/reset-password', [
            'email' => 'owner@example.com',
            'code' => '482913',
            'password' => 'new-pass-456',
        ])->assertStatus(422)->assertJsonValidationErrors('code');
    }
}
