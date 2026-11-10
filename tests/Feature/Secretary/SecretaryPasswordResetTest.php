<?php

namespace Tests\Feature\Secretary;

use App\Mail\SecretaryPasswordResetMail;
use App\Models\Secretary;
use App\Models\SecretaryPasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SecretaryPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_it_emails_a_reset_code_for_a_known_email(): void
    {
        $secretary = Secretary::factory()->create(['email' => 'staff@example.com']);

        $response = $this->postJson(route('secretary.password.forgot'), [
            'email' => 'staff@example.com',
        ]);

        $response->assertOk();
        Mail::assertSent(SecretaryPasswordResetMail::class);
        $this->assertDatabaseHas('secretary_password_resets', ['email' => 'staff@example.com']);
    }

    public function test_it_404s_for_an_unknown_email(): void
    {
        $response = $this->postJson(route('secretary.password.forgot'), [
            'email' => 'nobody@example.com',
        ]);

        $response->assertStatus(404);
        Mail::assertNothingSent();
    }

    public function test_it_resets_the_password_with_a_valid_code(): void
    {
        $secretary = Secretary::factory()->create(['email' => 'staff@example.com']);
        SecretaryPasswordReset::create([
            'email' => 'staff@example.com',
            'code' => '123456',
            'expires_at' => now()->addMinutes(30),
        ]);

        $response = $this->postJson(route('secretary.password.reset'), [
            'email' => 'staff@example.com',
            'code' => '123456',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ]);

        $response->assertOk();
        $this->assertTrue(Hash::check('new-secret-password', $secretary->fresh()->password));
        $this->assertDatabaseCount('secretary_password_resets', 0);
    }

    public function test_it_revokes_existing_tokens_on_reset(): void
    {
        $secretary = Secretary::factory()->create(['email' => 'staff@example.com']);
        $secretary->createToken('old-token');
        SecretaryPasswordReset::create([
            'email' => 'staff@example.com',
            'code' => '123456',
            'expires_at' => now()->addMinutes(30),
        ]);

        $this->postJson(route('secretary.password.reset'), [
            'email' => 'staff@example.com',
            'code' => '123456',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])->assertOk();

        $this->assertSame(0, $secretary->tokens()->count());
    }

    public function test_it_rejects_an_invalid_code(): void
    {
        Secretary::factory()->create(['email' => 'staff@example.com']);

        $response = $this->postJson(route('secretary.password.reset'), [
            'email' => 'staff@example.com',
            'code' => '000000',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ]);

        $response->assertStatus(422);
    }

    public function test_it_rejects_an_expired_code(): void
    {
        Secretary::factory()->create(['email' => 'staff@example.com']);
        SecretaryPasswordReset::create([
            'email' => 'staff@example.com',
            'code' => '123456',
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->postJson(route('secretary.password.reset'), [
            'email' => 'staff@example.com',
            'code' => '123456',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ]);

        $response->assertStatus(422);
    }

    public function test_it_requires_the_password_confirmation_to_match(): void
    {
        Secretary::factory()->create(['email' => 'staff@example.com']);
        SecretaryPasswordReset::create([
            'email' => 'staff@example.com',
            'code' => '123456',
            'expires_at' => now()->addMinutes(30),
        ]);

        $response = $this->postJson(route('secretary.password.reset'), [
            'email' => 'staff@example.com',
            'code' => '123456',
            'password' => 'new-secret-password',
            'password_confirmation' => 'does-not-match',
        ]);

        $response->assertStatus(422);
    }

    public function test_it_404s_when_resetting_for_an_unknown_email(): void
    {
        $response = $this->postJson(route('secretary.password.reset'), [
            'email' => 'nobody@example.com',
            'code' => '123456',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ]);

        $response->assertStatus(404);
    }

    public function test_it_invalidates_the_reset_code_after_five_wrong_attempts(): void
    {
        $secretary = Secretary::factory()->create(['email' => 'staff@example.com']);
        $reset = SecretaryPasswordReset::create([
            'email' => 'staff@example.com',
            'code' => '123456',
            'expires_at' => now()->addMinutes(30),
        ]);

        foreach (range(1, SecretaryPasswordReset::MAX_ATTEMPTS) as $attempt) {
            $this->postJson(route('secretary.password.reset'), [
                'email' => 'staff@example.com',
                'code' => '000000',
                'password' => 'new-secret-password',
                'password_confirmation' => 'new-secret-password',
            ])->assertStatus(422);
        }

        $this->assertModelMissing($reset);
        $this->assertFalse(Hash::check('new-secret-password', $secretary->fresh()->password));
    }

    public function test_forgot_password_is_throttled_per_email(): void
    {
        Secretary::factory()->create(['email' => 'staff@example.com']);

        $this->postJson(route('secretary.password.forgot'), ['email' => 'staff@example.com'])->assertOk();

        $this->postJson(route('secretary.password.forgot'), ['email' => 'staff@example.com'])->assertTooManyRequests();

        Mail::assertSentCount(1);
    }
}
