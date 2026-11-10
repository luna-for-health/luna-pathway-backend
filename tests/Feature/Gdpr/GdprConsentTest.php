<?php

namespace Tests\Feature\Gdpr;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GdprConsentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_is_public_and_returns_four_steps(): void
    {
        $response = $this->getJson(route('gdpr.consent', ['email' => 'unknown@example.com']));

        $response->assertOk();
        $this->assertCount(4, $response->json('data.gdpr'));
        $response->assertJsonPath('data.user', null);
    }

    public function test_it_returns_only_the_gdpr_acceptance_when_the_email_is_known(): void
    {
        User::factory()->create(['email' => 'doctor@example.com', 'has_accepted_gdpr' => true]);

        $response = $this->getJson(route('gdpr.consent', ['email' => 'doctor@example.com']));

        $response->assertOk();
        $response->assertJsonPath('data.user', ['has_accepted_gdpr' => true]);
    }

    public function test_it_returns_false_when_the_user_has_not_accepted_gdpr(): void
    {
        User::factory()->create(['email' => 'doctor@example.com', 'has_accepted_gdpr' => false]);

        $response = $this->getJson(route('gdpr.consent', ['email' => 'doctor@example.com']));

        $response->assertOk();
        $response->assertJsonPath('data.user', ['has_accepted_gdpr' => false]);
    }

    public function test_it_requires_a_valid_email(): void
    {
        $response = $this->getJson(route('gdpr.consent', ['email' => 'not-an-email']));

        $response->assertStatus(422);
    }
}
