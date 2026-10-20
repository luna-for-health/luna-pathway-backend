<?php

namespace Tests\Feature\Appointment;

use App\Jobs\SendBrevoMailJob;
use App\Models\ConsultationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConsultationRequestConfirmationEmailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    protected function payload(): array
    {
        return [
            'reason' => 'Pelvic pain',
            'dysmenorrhea' => 5,
            'pelvicPain' => 5,
            'dyspareunia' => 5,
            'digestiveIssues' => 5,
            'urinaryIssues' => 5,
            'fatigue' => 5,
        ];
    }

    public function test_store_queues_the_brevo_confirmation_template_to_the_patient(): void
    {
        config(['services.brevo.templates.consultation_request_created' => '12']);
        $user = User::factory()->create(['first_name' => 'Sara', 'last_name' => 'Martin', 'email' => 'sara@example.com']);
        Sanctum::actingAs($user);
        $this->travelTo('2026-10-13 09:30:00');
        Queue::fake([SendBrevoMailJob::class]);

        $response = $this->postJson(route('consultationRequests.store'), $this->payload());

        $response->assertOk();
        $consultationRequest = ConsultationRequest::sole();
        Queue::assertPushed(SendBrevoMailJob::class, fn (SendBrevoMailJob $job): bool => $job->message->to === [['email' => 'sara@example.com']]
            && $job->message->templateId === 12
            && $job->message->params === [
                'PRENOM' => 'Martin',
                'lastName' => 'Martin',
                'firstName' => 'Sara',
                'consultationRequestRef' => $consultationRequest->id,
                'createdAt' => '13/10/2026 09:30',
            ]
            && $job->message->tags === ['consultation-request-created']);
    }

    public function test_store_queues_no_email_when_the_template_is_not_configured(): void
    {
        config(['services.brevo.templates.consultation_request_created' => null]);
        Sanctum::actingAs(User::factory()->create());
        Queue::fake([SendBrevoMailJob::class]);

        $response = $this->postJson(route('consultationRequests.store'), $this->payload());

        $response->assertOk();
        Queue::assertNotPushed(SendBrevoMailJob::class);
    }
}
