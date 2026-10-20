<?php

namespace Tests\Feature\Appointment;

use App\Models\ConsultationRequest;
use App\Models\Secretary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecretaryConsultationRequestIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_secretary_sees_requests_from_every_patient(): void
    {
        ConsultationRequest::factory()->for(User::factory())->create();
        ConsultationRequest::factory()->for(User::factory())->create();
        ConsultationRequest::factory()->for(User::factory())->create();

        Sanctum::actingAs(Secretary::factory()->create());

        $response = $this->getJson(route('secretary.consultationRequests.index'));

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_it_returns_the_oldest_requests_first(): void
    {
        $newerRequest = ConsultationRequest::factory()->create(['created_at' => now()]);
        $olderRequest = ConsultationRequest::factory()->create(['created_at' => now()->subDay()]);

        Sanctum::actingAs(Secretary::factory()->create());

        $response = $this->getJson(route('secretary.consultationRequests.index'));

        $response->assertOk();
        $this->assertSame(
            [$olderRequest->id, $newerRequest->id],
            array_column($response->json('data'), 'id')
        );
    }

    public function test_each_item_includes_the_owning_patient(): void
    {
        $user = User::factory()->create();
        ConsultationRequest::factory()->for($user)->create();

        Sanctum::actingAs(Secretary::factory()->create());

        $response = $this->getJson(route('secretary.consultationRequests.index'));

        $response->assertOk();
        $response->assertJsonPath('data.0.user.id', $user->id);
        $response->assertJsonPath('data.0.user.email', $user->email);
    }

    public function test_it_filters_by_status(): void
    {
        ConsultationRequest::factory()->for(User::factory())->create();
        ConsultationRequest::factory()->for(User::factory())->treated()->create();

        Sanctum::actingAs(Secretary::factory()->create());

        $response = $this->getJson(route('secretary.consultationRequests.index', ['status' => 'treated']));

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $response->assertJsonPath('data.0.status', 'treated');
    }

    public function test_it_paginates_results(): void
    {
        ConsultationRequest::factory()->for(User::factory())->count(3)->create();

        Sanctum::actingAs(Secretary::factory()->create());

        $response = $this->getJson(route('secretary.consultationRequests.index', ['per_page' => 2]));

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
        $response->assertJsonPath('pagination.total', 3);
    }

    public function test_a_patient_cannot_list_all_requests(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson(route('secretary.consultationRequests.index'));

        $response->assertStatus(403);
    }

    public function test_it_requires_authentication(): void
    {
        $response = $this->getJson(route('secretary.consultationRequests.index'));

        $response->assertStatus(401);
    }
}
