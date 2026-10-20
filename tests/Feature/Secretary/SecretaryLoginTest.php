<?php

namespace Tests\Feature\Secretary;

use App\Models\Secretary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecretaryLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_logs_in_with_valid_credentials(): void
    {
        $secretary = Secretary::factory()->create(['email' => 'staff@example.com']);

        $response = $this->postJson(route('secretary.login'), [
            'email' => 'staff@example.com',
            'password' => 'password',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.secretary.id', $secretary->id);
        $this->assertNotEmpty($response->json('data.access_token'));
    }

    public function test_it_rejects_an_invalid_password(): void
    {
        Secretary::factory()->create(['email' => 'staff@example.com']);

        $response = $this->postJson(route('secretary.login'), [
            'email' => 'staff@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
    }

    public function test_it_rejects_an_unknown_email(): void
    {
        $response = $this->postJson(route('secretary.login'), [
            'email' => 'nobody@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(401);
    }

    public function test_logout_revokes_the_token(): void
    {
        $secretary = Secretary::factory()->create();
        $token = $secretary->createToken('test')->plainTextToken;

        $response = $this->postJson(route('secretary.logout'), [], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
