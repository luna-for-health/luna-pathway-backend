<?php

namespace Tests\Feature\Gdpr;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GdprAcceptTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_marks_the_authenticated_user_as_having_accepted_gdpr(): void
    {
        $user = User::factory()->create(['has_accepted_gdpr' => false]);

        Sanctum::actingAs($user);

        $response = $this->putJson(route('gdpr.accept'));

        $response->assertOk();
        $response->assertJsonPath('data.has_accepted_gdpr', true);
        $this->assertTrue($user->fresh()->has_accepted_gdpr);
    }

    public function test_it_requires_authentication(): void
    {
        $response = $this->putJson(route('gdpr.accept'));

        $response->assertStatus(401);
    }
}
