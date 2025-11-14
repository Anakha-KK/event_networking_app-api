<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_user_from_a_valid_google_token(): void
    {
        config(['services.google.mobile_client_ids' => ['test-client']]);

        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'aud' => 'test-client',
                'iss' => 'https://accounts.google.com',
                'exp' => now()->addHour()->timestamp,
                'email' => 'newuser@example.com',
                'email_verified' => 'true',
                'name' => 'New User',
                'given_name' => 'New',
                'family_name' => 'User',
                'picture' => 'https://example.com/avatar.png',
            ], 200),
        ]);

        $response = $this->postJson('/api/auth/google/mobile', [
            'id_token' => 'fake-token',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('user.email', 'newuser@example.com')
            ->assertJsonStructure(['token', 'user' => ['id', 'email', 'profile']]);

        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
        $this->assertDatabaseHas('user_profiles', ['avatar_url' => 'https://example.com/avatar.png']);
    }

    public function test_it_returns_validation_error_for_invalid_google_token(): void
    {
        config(['services.google.mobile_client_ids' => ['test-client']]);

        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([], 400),
        ]);

        $response = $this->postJson('/api/auth/google/mobile', [
            'id_token' => 'expired-token',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('id_token');
    }
}
