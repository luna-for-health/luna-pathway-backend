<?php

namespace App\Jobs;

use App\Models\ConsultationDocument;
use App\Services\Ocr\OcrServiceFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExtractConsultationDocumentOcr implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public ConsultationDocument $document) {}

    public function handle(): void
    {
        $path = 'public/consultation-documents/'.$this->document->path;

        if (! Storage::exists($path)) {
            $this->storeFailure('document file not found.');

            return;
        }

        try {
            $contents = Crypt::decrypt(Storage::get($path));
            $service = OcrServiceFactory::make();

            $fullText = $service->extractText(
                $contents,
                $this->document->mime_type ?? 'application/pdf'
            );

            $summary = $service->summarize($fullText);

            $this->document->update([
                'ocr_full_text' => $fullText,
                'ocr_text' => $summary,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Consultation document OCR failed', [
                'document_id' => $this->document->id,
                'exception' => $exception->getMessage(),
            ]);

            $this->storeFailure($exception->getMessage());
        }
    }

    private function storeFailure(string $reason): void
    {
        $this->document->update(['ocr_text' => 'OCR failed: '.$reason]);
    }
}
