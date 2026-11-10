<?php

namespace Tests\Feature\Auth;

use App\Mail\MagicCodeMail;
use App\Models\MagicCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MagicCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    private function createMagicCode(string $code = '123456'): MagicCode
    {
        return MagicCode::create([
            'email' => 'patient@example.com',
            'code' => $code,
            'expires_at' => now()->addMinutes(30),
        ]);
    }

    public function test_it_invalidates_the_code_after_five_wrong_attempts(): void
    {
        $magicCode = $this->createMagicCode();

        foreach (range(1, MagicCode::MAX_ATTEMPTS) as $attempt) {
            $this->postJson(route('auth.verifyCode'), ['email' => 'patient@example.com', 'code' => '000000'])
                ->assertStatus(422);
        }

        $this->assertModelMissing($magicCode);
    }

    public function test_the_correct_code_still_works_after_fewer_than_five_wrong_attempts(): void
    {
        $this->createMagicCode();

        foreach (range(1, MagicCode::MAX_ATTEMPTS - 1) as $attempt) {
            $this->postJson(route('auth.verifyCode'), ['email' => 'patient@example.com', 'code' => '000000'])
                ->assertStatus(422);
        }

        $this->postJson(route('auth.verifyCode'), ['email' => 'patient@example.com', 'code' => '123456'])
            ->assertOk();
    }

    public function test_verify_code_is_throttled_per_email_and_ip(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->postJson(route('auth.verifyCode'), ['email' => 'patient@example.com', 'code' => '000000'])
                ->assertStatus(422);
        }

        $this->postJson(route('auth.verifyCode'), ['email' => 'patient@example.com', 'code' => '000000'])
            ->assertTooManyRequests();
    }

    public function test_send_code_is_throttled_per_email(): void
    {
        $this->postJson(route('auth.sendCode'), ['email' => 'patient@example.com'])->assertOk();

        $this->postJson(route('auth.sendCode'), ['email' => 'patient@example.com'])->assertTooManyRequests();

        Mail::assertSentCount(1);
        Mail::assertSent(MagicCodeMail::class);
    }

    public function test_rate_limits_use_the_forwarded_client_ip_behind_a_trusted_proxy(): void
    {
        config(['trustedproxy.proxies' => '*']);

        foreach (range(1, 10) as $index) {
            $this->withHeader('X-Forwarded-For', '203.0.113.1')
                ->postJson(route('auth.sendCode'), ['email' => "patient{$index}@example.com"])
                ->assertOk();
        }

        $this->withHeader('X-Forwarded-For', '203.0.113.1')
            ->postJson(route('auth.sendCode'), ['email' => 'patient11@example.com'])
            ->assertTooManyRequests();

        $this->withHeader('X-Forwarded-For', '203.0.113.2')
            ->postJson(route('auth.sendCode'), ['email' => 'patient12@example.com'])
            ->assertOk();
    }
}
