<?php

namespace Tests\Feature\Appointment;

use App\Models\ConsultationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConsultationRequestIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_only_the_authenticated_users_requests(): void
    {
        $user = User::factory()->create();
        ConsultationRequest::factory()->for($user)->count(2)->create();
        ConsultationRequest::factory()->create(); // belongs to another user

        Sanctum::actingAs($user);

        $response = $this->getJson(route('consultationRequests.index'));

        $response->assertOk();
        $this->assertCount(2, $response->json('data.consultationRequests'));
    }

    public function test_it_returns_the_oldest_requests_first(): void
    {
        $user = User::factory()->create();
        $newerRequest = ConsultationRequest::factory()->for($user)->create(['created_at' => now()]);
        $olderRequest = ConsultationRequest::factory()->for($user)->create(['created_at' => now()->subDay()]);

        Sanctum::actingAs($user);

        $response = $this->getJson(route('consultationRequests.index'));

        $response->assertOk();
        $this->assertSame(
            [$olderRequest->id, $newerRequest->id],
            array_column($response->json('data.consultationRequests'), 'id')
        );
    }

    public function test_it_includes_the_authenticated_user(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson(route('consultationRequests.index'));

        $response->assertOk();
        $response->assertJsonPath('data.user.id', $user->id);
        $response->assertJsonPath('data.user.email', $user->email);
    }

    public function test_individual_requests_do_not_duplicate_the_user(): void
    {
        $user = User::factory()->create();
        ConsultationRequest::factory()->for($user)->create();

        Sanctum::actingAs($user);

        $response = $this->getJson(route('consultationRequests.index'));

        $response->assertOk();
        $this->assertArrayNotHasKey('user', $response->json('data.consultationRequests.0'));
    }

    public function test_it_filters_by_status(): void
    {
        $user = User::factory()->create();
        ConsultationRequest::factory()->for($user)->count(2)->create();
        ConsultationRequest::factory()->for($user)->treated()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson(route('consultationRequests.index', ['status' => 'treated']));

        $response->assertOk();
        $this->assertCount(1, $response->json('data.consultationRequests'));
        $response->assertJsonPath('data.consultationRequests.0.status', 'treated');
    }

    public function test_new_is_the_default_status(): void
    {
        $user = User::factory()->create();
        ConsultationRequest::factory()->for($user)->create();

        Sanctum::actingAs($user);

        $response = $this->getJson(route('consultationRequests.index', ['status' => 'new']));

        $response->assertOk();
        $this->assertCount(1, $response->json('data.consultationRequests'));
    }

    public function test_it_returns_everything_when_no_filter_is_given(): void
    {
        $user = User::factory()->create();
        ConsultationRequest::factory()->for($user)->count(2)->create();
        ConsultationRequest::factory()->for($user)->validated()->create();
        ConsultationRequest::factory()->for($user)->treated()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson(route('consultationRequests.index'));

        $response->assertOk();
        $this->assertCount(4, $response->json('data.consultationRequests'));
    }

    public function test_it_paginates_results(): void
    {
        $user = User::factory()->create();
        ConsultationRequest::factory()->for($user)->count(3)->create();

        Sanctum::actingAs($user);

        $response = $this->getJson(route('consultationRequests.index', ['per_page' => 2]));

        $response->assertOk();
        $this->assertCount(2, $response->json('data.consultationRequests'));
        $response->assertJsonPath('pagination.total', 3);
        $response->assertJsonPath('pagination.perPage', 2);
        $response->assertJsonPath('pagination.hasMorePages', true);
    }

    public function test_it_rejects_an_invalid_status(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson(route('consultationRequests.index', ['status' => 'bogus']));

        $response->assertStatus(422);
    }

    public function test_it_requires_authentication(): void
    {
        $response = $this->getJson(route('consultationRequests.index'));

        $response->assertStatus(401);
    }
}
