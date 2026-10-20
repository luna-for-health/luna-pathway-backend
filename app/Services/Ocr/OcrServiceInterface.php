<?php

namespace App\Services\Ocr;

interface OcrServiceInterface
{
    /**
     * Extract the textual content of a document.
     *
     * @param  string  $contents  Raw (decrypted) binary contents of the file.
     * @param  string  $mimeType  MIME type of the file (e.g. "application/pdf", "image/png").
     * @return string The extracted text.
     *
     * @throws \RuntimeException When the provider is unreachable or returns an unusable response.
     */
    public function extractText(string $contents, string $mimeType): string;

    /**
     * Extract a structured summary from the previously OCR'd text.
     *
     * @param  string  $fullText  The full raw text extracted via extractText().
     * @return array{title: string, category: string, subcategory: string, exam_date: string, conclusion: string}
     *
     * @throws \RuntimeException When the provider is unreachable or returns an unusable response.
     */
    public function summarize(string $fullText): array;
}
