<?php

namespace Tests\Feature\Appointment;

use App\Models\ConsultationDocument;
use App\Models\ConsultationRequest;
use App\Models\Secretary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConsultationDocumentDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
    }

    private function storedDocument(ConsultationRequest $consultationRequest, string $contents = 'binary-file'): ConsultationDocument
    {
        $document = ConsultationDocument::factory()->for($consultationRequest)->create();

        Storage::put(
            'public/consultation-documents/'.$document->path,
            Crypt::encrypt($contents)
        );

        return $document;
    }

    public function test_the_owning_patient_can_download_their_document(): void
    {
        $user = User::factory()->create();
        $consultationRequest = ConsultationRequest::factory()->for($user)->create();
        $document = $this->storedDocument($consultationRequest, 'my file contents');

        Sanctum::actingAs($user);

        $response = $this->get(route('consultationDocuments.download', $document));

        $response->assertOk();
        $this->assertSame('my file contents', $response->streamedContent());
    }

    public function test_a_different_patient_cannot_download_it(): void
    {
        $consultationRequest = ConsultationRequest::factory()->for(User::factory())->create();
        $document = $this->storedDocument($consultationRequest);

        Sanctum::actingAs(User::factory()->create());

        $response = $this->get(route('consultationDocuments.download', $document));

        $response->assertStatus(403);
    }

    public function test_a_secretary_can_download_any_document(): void
    {
        $consultationRequest = ConsultationRequest::factory()->for(User::factory())->create();
        $document = $this->storedDocument($consultationRequest, 'staff can see this');

        Sanctum::actingAs(Secretary::factory()->create());

        $response = $this->get(route('consultationDocuments.download', $document));

        $response->assertOk();
        $this->assertSame('staff can see this', $response->streamedContent());
    }

    public function test_it_requires_authentication(): void
    {
        $consultationRequest = ConsultationRequest::factory()->for(User::factory())->create();
        $document = $this->storedDocument($consultationRequest);

        $response = $this->get(route('consultationDocuments.download', $document));

        $response->assertStatus(401);
    }

    public function test_it_404s_when_the_file_is_missing_from_storage(): void
    {
        $user = User::factory()->create();
        $consultationRequest = ConsultationRequest::factory()->for($user)->create();
        $document = ConsultationDocument::factory()->for($consultationRequest)->create();

        Sanctum::actingAs($user);

        $response = $this->get(route('consultationDocuments.download', $document));

        $response->assertStatus(404);
    }

    public function test_the_consultation_request_response_includes_a_download_url(): void
    {
        $user = User::factory()->create();
        $consultationRequest = ConsultationRequest::factory()->for($user)->create();
        $document = $this->storedDocument($consultationRequest);

        Sanctum::actingAs($user);

        $response = $this->getJson(route('consultationRequests.index'));

        $response->assertOk();
        $response->assertJsonPath(
            'data.consultationRequests.0.documents.0.url',
            route('consultationDocuments.download', $document)
        );
    }
}
