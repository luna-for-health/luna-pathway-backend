<?php

namespace Tests\Feature\Onboarding;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_is_public_and_returns_five_steps(): void
    {
        $response = $this->getJson(route('onboarding.content'));

        $response->assertOk();
        $this->assertCount(5, $response->json('data'));
        $response->assertJsonPath('data.0.step', 1);
    }

    public function test_update_marks_onboarding_complete(): void
    {
        $user = User::factory()->create(['has_completed_onboarding' => false]);

        Sanctum::actingAs($user);

        $response = $this->putJson(route('onboarding.update'), [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'phone' => '+21611223344',
            'avatar' => 3,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.user.has_completed_onboarding', true);
        $response->assertJsonPath('data.user.first_name', 'Updated');
        $response->assertJsonPath('data.user.last_name', 'Name');
        $response->assertJsonPath('data.user.avatar', 3);
        $this->assertNotEmpty($response->json('data.access_token'));

        $user->refresh();
        $this->assertTrue($user->has_completed_onboarding);
        $this->assertSame('+21611223344', $user->phone);
        $this->assertSame(3, $user->avatar_id);
    }

    public function test_update_works_without_optional_fields(): void
    {
        $user = User::factory()->create(['first_name' => 'Original']);

        Sanctum::actingAs($user);

        $response = $this->putJson(route('onboarding.update'), []);

        $response->assertOk();
        $response->assertJsonPath('data.user.first_name', 'Original');
        $this->assertTrue($user->fresh()->has_completed_onboarding);
    }

    public function test_update_requires_authentication(): void
    {
        $response = $this->putJson(route('onboarding.update'), []);

        $response->assertStatus(401);
    }
}
