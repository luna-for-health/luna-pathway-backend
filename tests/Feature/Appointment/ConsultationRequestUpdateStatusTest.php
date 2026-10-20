<?php

namespace Tests\Feature\Appointment;

use App\Models\ConsultationRequest;
use App\Models\Secretary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConsultationRequestUpdateStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_secretary_can_update_the_status(): void
    {
        $consultationRequest = ConsultationRequest::factory()->create();

        Sanctum::actingAs(Secretary::factory()->create());

        $response = $this->patchJson(
            route('consultationRequests.updateStatus', $consultationRequest),
            ['status' => 'validated']
        );

        $response->assertOk();
        $response->assertJsonPath('data.status', 'validated');
        $this->assertSame('validated', $consultationRequest->fresh()->status->value);
    }

    public function test_it_records_the_status_reason(): void
    {
        $consultationRequest = ConsultationRequest::factory()->create();

        Sanctum::actingAs(Secretary::factory()->create());

        $response = $this->patchJson(
            route('consultationRequests.updateStatus', $consultationRequest),
            ['status' => 'validated', 'status_reason' => 'Patient confirmed by phone']
        );

        $response->assertOk();
        $response->assertJsonPath('data.statusReason', 'Patient confirmed by phone');
        $this->assertSame('Patient confirmed by phone', $consultationRequest->fresh()->status_reason);
    }

    public function test_status_reason_is_optional(): void
    {
        $consultationRequest = ConsultationRequest::factory()->create();

        Sanctum::actingAs(Secretary::factory()->create());

        $response = $this->patchJson(
            route('consultationRequests.updateStatus', $consultationRequest),
            ['status' => 'validated']
        );

        $response->assertOk();
        $response->assertJsonPath('data.statusReason', null);
    }

    public function test_it_can_update_any_patients_request_not_just_one(): void
    {
        $consultationRequest = ConsultationRequest::factory()->for(User::factory())->create();

        Sanctum::actingAs(Secretary::factory()->create());

        $response = $this->patchJson(
            route('consultationRequests.updateStatus', $consultationRequest),
            ['status' => 'treated']
        );

        $response->assertOk();
    }

    public function test_a_patient_cannot_update_status(): void
    {
        $consultationRequest = ConsultationRequest::factory()->create();

        Sanctum::actingAs(User::factory()->create());

        $response = $this->patchJson(
            route('consultationRequests.updateStatus', $consultationRequest),
            ['status' => 'validated']
        );

        $response->assertStatus(403);
    }

    public function test_it_requires_authentication(): void
    {
        $consultationRequest = ConsultationRequest::factory()->create();

        $response = $this->patchJson(
            route('consultationRequests.updateStatus', $consultationRequest),
            ['status' => 'validated']
        );

        $response->assertStatus(401);
    }

    public function test_it_rejects_an_invalid_status(): void
    {
        $consultationRequest = ConsultationRequest::factory()->create();

        Sanctum::actingAs(Secretary::factory()->create());

        $response = $this->patchJson(
            route('consultationRequests.updateStatus', $consultationRequest),
            ['status' => 'bogus']
        );

        $response->assertStatus(422);
    }
}
