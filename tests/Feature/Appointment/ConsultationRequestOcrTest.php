<?php

namespace Tests\Feature\Appointment;

use App\Jobs\ExtractConsultationDocumentOcr;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConsultationRequestOcrTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
        Queue::fake();
    }

    /**
     * @return array<string, int>
     */
    protected function symptomScores(): array
    {
        return [
            'dysmenorrhea' => 5,
            'pelvicPain' => 5,
            'dyspareunia' => 5,
            'digestiveIssues' => 5,
            'urinaryIssues' => 5,
            'fatigue' => 5,
        ];
    }

    public function test_store_dispatches_an_ocr_job_for_each_uploaded_document(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson(route('consultationRequests.store'), [
            'reason' => 'Pelvic pain',
            ...$this->symptomScores(),
            'documents' => [
                UploadedFile::fake()->create('history.pdf', 120, 'application/pdf'),
                UploadedFile::fake()->image('scan.png'),
            ],
        ]);

        $response->assertOk();
        Queue::assertPushed(ExtractConsultationDocumentOcr::class, 2);
    }

    public function test_store_dispatches_no_ocr_job_when_no_document_is_attached(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson(route('consultationRequests.store'), [
            'reason' => 'Pelvic pain',
            ...$this->symptomScores(),
        ]);

        $response->assertOk();
        Queue::assertNothingPushed();
    }

    public function test_store_defaults_the_status_to_new(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson(route('consultationRequests.store'), [
            'reason' => 'Pelvic pain',
            ...$this->symptomScores(),
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', 'new');
    }

    public function test_store_response_exposes_the_ocr_text_field(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson(route('consultationRequests.store'), [
            'reason' => 'Pelvic pain',
            ...$this->symptomScores(),
            'documents' => [UploadedFile::fake()->create('history.pdf', 120, 'application/pdf')],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.documents.0.ocrText', null);
        $response->assertJsonPath('data.documents.0.ocrFullText', null);
    }

    public function test_store_defaults_has_mri_to_false(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson(route('consultationRequests.store'), [
            'reason' => 'Pelvic pain',
            ...$this->symptomScores(),
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.hasMri', false);
    }

    public function test_store_accepts_has_mri(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson(route('consultationRequests.store'), [
            'reason' => 'Pelvic pain',
            ...$this->symptomScores(),
            'hasMri' => true,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.hasMri', true);
    }
}
