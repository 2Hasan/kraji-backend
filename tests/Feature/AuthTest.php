<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_returns_token(): void
    {
        $this->postJson('/api/auth/register', [
            'email' => 'owner@example.com',
            'password' => 'secret-pass-123',
            'name' => 'Owner',
        ])->assertCreated()->assertJsonStructure(['token', 'user' => ['id', 'email']]);
    }

    public function test_login_with_wrong_password_returns_generic_422(): void
    {
        User::factory()->create(['email' => 'owner@example.com', 'password' => bcrypt('right-pass-123')]);

        $this->postJson('/api/auth/login', [
            'email' => 'owner@example.com',
            'password' => 'wrong-pass',
        ])->assertStatus(422);
    }

    public function test_register_with_duplicate_email_returns_422(): void
    {
        User::factory()->create(['email' => 'owner@example.com']);

        $this->postJson('/api/auth/register', [
            'email' => 'owner@example.com',
            'password' => 'secret-pass-123',
            'name' => 'Other',
        ])->assertStatus(422);
    }
}
