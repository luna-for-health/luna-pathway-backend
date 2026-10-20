<?php

namespace Tests\Feature\Appointment;

use App\Enums\ConsultationRequestStatus;
use App\Models\ConsultationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResetReminderConsultationRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_moves_reminder_requests_back_to_new_and_leaves_others_untouched(): void
    {
        $reminderRequest = ConsultationRequest::factory()->reminder()->create();
        $validatedRequest = ConsultationRequest::factory()->validated()->create();
        $treatedRequest = ConsultationRequest::factory()->treated()->create();

        $this->artisan('consultation-requests:reset-reminders')->assertSuccessful();

        $this->assertSame(ConsultationRequestStatus::New, $reminderRequest->fresh()->status);
        $this->assertSame(ConsultationRequestStatus::Validated, $validatedRequest->fresh()->status);
        $this->assertSame(ConsultationRequestStatus::Treated, $treatedRequest->fresh()->status);
    }
}
