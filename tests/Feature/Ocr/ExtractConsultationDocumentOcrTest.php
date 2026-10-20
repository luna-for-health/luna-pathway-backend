<?php

namespace Tests\Feature\Ocr;

use App\Jobs\ExtractConsultationDocumentOcr;
use App\Models\ConsultationDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExtractConsultationDocumentOcrTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
        config(['ocr.provider' => 'mistral', 'ocr.mistral.endpoint' => 'https://api.mistral.ai/v1/ocr']);
    }

    private function storedDocument(string $contents = 'binary-file'): ConsultationDocument
    {
        $document = ConsultationDocument::factory()->create();

        Storage::put(
            'public/consultation-documents/'.$document->path,
            Crypt::encrypt($contents)
        );

        return $document;
    }

    public function test_it_stores_the_full_text_and_the_structured_summary_on_the_document(): void
    {
        Http::fake([
            'api.mistral.ai/v1/ocr' => Http::response([
                'pages' => [
                    ['markdown' => '# Page 1'],
                    ['markdown' => 'Page 2 body'],
                ],
            ]),
            'api.mistral.ai/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'title' => 'Analyse de sang',
                        'category' => 'compte_rendu',
                        'subcategory' => 'cr_consultation',
                        'exam_date' => '12/03/2026',
                        'conclusion' => 'Bilan dans les normes.',
                    ])]],
                ],
            ]),
        ]);

        $document = $this->storedDocument();

        (new ExtractConsultationDocumentOcr($document))->handle();

        $document->refresh();

        $this->assertSame("# Page 1\n\nPage 2 body", $document->ocr_full_text);
        $this->assertSame(
            [
                'title' => 'Analyse de sang',
                'category' => 'compte_rendu',
                'subcategory' => 'cr_consultation',
                'exam_date' => '12/03/2026',
                'conclusion' => 'Bilan dans les normes.',
            ],
            $document->ocr_text
        );
    }

    public function test_it_writes_the_error_message_when_the_provider_returns_an_error(): void
    {
        Http::fake(['api.mistral.ai/*' => Http::response(['error' => 'boom'], 500)]);

        $document = $this->storedDocument();

        (new ExtractConsultationDocumentOcr($document))->handle();

        $document->refresh();

        $this->assertStringStartsWith('OCR failed:', $document->ocr_text);
        $this->assertNull($document->ocr_full_text);
    }

    public function test_it_writes_the_error_message_when_the_summary_provider_returns_an_error(): void
    {
        Http::fake([
            'api.mistral.ai/v1/ocr' => Http::response([
                'pages' => [['markdown' => '# Page 1']],
            ]),
            'api.mistral.ai/v1/chat/completions' => Http::response(['error' => 'boom'], 500),
        ]);

        $document = $this->storedDocument();

        (new ExtractConsultationDocumentOcr($document))->handle();

        $document->refresh();

        $this->assertStringStartsWith('OCR failed:', $document->ocr_text);
        $this->assertNull($document->ocr_full_text);
    }

    public function test_it_writes_the_error_message_when_the_file_is_missing(): void
    {
        Http::fake();

        $document = ConsultationDocument::factory()->create();

        (new ExtractConsultationDocumentOcr($document))->handle();

        $this->assertSame('OCR failed: document file not found.', $document->fresh()->ocr_text);
        Http::assertNothingSent();
    }
}
