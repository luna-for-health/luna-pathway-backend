<?php

namespace App\Services\Ocr;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MistralOcrService implements OcrServiceInterface
{
    private const SUMMARY_SYSTEM_PROMPT = <<<'PROMPT'
        Tu es un assistant médical qui structure des documents de santé déjà transcrits par OCR.
        À partir du texte fourni, réponds uniquement avec un objet JSON contenant exactement les clés suivantes :
        - "title": un titre suggéré, court et explicite, pour ce document.
        - "category": la catégorie du document, classée STRICTEMENT selon la liste fermée suivante :
          - compte_rendu
          - imagerie
          - non_classe (si le type du document ne peut pas être déterminé parmi les catégories ci-dessus)
        - "subcategory": la sous-catégorie du document, STRICTEMENT parmi celles associées à la catégorie choisie :
          - compte_rendu : courrier_medical, cr_consultation, cr_operatoire, cr_anatomopathologique, cr_hospitalisation
          - imagerie : echo_gyneco, echo_follicules, irm_pelvienne, irm_diaphragmatique, irm_abdominal, scanner, coloscopie, echo_endoscopie_rectale
          - non_classe : aucune sous-catégorie, renvoie une chaîne vide.
          N'invente jamais de catégorie ou de sous-catégorie en dehors de cette liste.
        - "exam_date": la date exacte de l'examen ou de la consultation telle qu'elle apparaît dans le document, au format JJ/MM/AAAA. N'utilise jamais la date du jour : si aucune date d'examen n'est mentionnée dans le document, renvoie une chaîne vide.
        - "conclusion": la conclusion complète du document, recopiée mot pour mot, sans résumé ni reformulation. Si aucune conclusion explicite n'existe, renvoie une chaîne vide.
        Ne renvoie aucun texte en dehors de l'objet JSON.
        PROMPT;

    public function extractText(string $contents, string $mimeType): string
    {
        $dataUri = 'data:'.$mimeType.';base64,'.base64_encode($contents);

        $document = str_starts_with($mimeType, 'image/')
            ? ['type' => 'image_url', 'image_url' => $dataUri]
            : ['type' => 'document_url', 'document_url' => $dataUri];

        try {
            $response = Http::withToken(config('ocr.mistral.api_key'))
                ->timeout((int) config('ocr.mistral.timeout', 120))
                ->post(config('ocr.mistral.endpoint'), [
                    'model' => config('ocr.mistral.model'),
                    'document' => $document,
                ]);
        } catch (\Throwable $exception) {
            Log::error('Mistral OCR request failed', ['exception' => $exception->getMessage()]);

            throw new RuntimeException('OCR provider unavailable: '.$exception->getMessage(), 0, $exception);
        }

        if (! $response->successful()) {
            Log::error('Mistral OCR request returned an error status', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('OCR provider returned HTTP '.$response->status());
        }

        return $this->extractMarkdown($response->json());
    }

    public function summarize(string $fullText): array
    {
        try {
            $response = Http::withToken(config('ocr.mistral.api_key'))
                ->timeout((int) config('ocr.mistral.timeout', 120))
                ->post(config('ocr.mistral.summary_endpoint'), [
                    'model' => config('ocr.mistral.summary_model'),
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => self::SUMMARY_SYSTEM_PROMPT],
                        ['role' => 'user', 'content' => $fullText],
                    ],
                ]);
        } catch (\Throwable $exception) {
            Log::error('Mistral OCR summary request failed', ['exception' => $exception->getMessage()]);

            throw new RuntimeException('OCR summary provider unavailable: '.$exception->getMessage(), 0, $exception);
        }

        if (! $response->successful()) {
            Log::error('Mistral OCR summary request returned an error status', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('OCR summary provider returned HTTP '.$response->status());
        }

        return $this->extractSummary($response->json('choices.0.message.content'));
    }

    /**
     * @return array{title: string, category: string, subcategory: string, exam_date: string, conclusion: string}
     */
    private function extractSummary(mixed $content): array
    {
        $decoded = is_string($content) ? json_decode($content, true) : null;

        if (! is_array($decoded)) {
            Log::error('Mistral OCR summary response was not valid JSON', ['content' => $content]);

            throw new RuntimeException('OCR summary provider returned an unusable response.');
        }

        return [
            'title' => (string) ($decoded['title'] ?? ''),
            'category' => (string) ($decoded['category'] ?? ''),
            'subcategory' => (string) ($decoded['subcategory'] ?? ''),
            'exam_date' => (string) ($decoded['exam_date'] ?? ''),
            'conclusion' => (string) ($decoded['conclusion'] ?? ''),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    private function extractMarkdown(?array $data): string
    {
        $pages = $data['pages'] ?? [];

        $markdown = collect($pages)
            ->pluck('markdown')
            ->filter(static fn ($value): bool => is_string($value) && $value !== '')
            ->implode("\n\n");

        if ($markdown === '') {
            Log::error('Mistral OCR response contained no text', ['response' => $data]);

            throw new RuntimeException('OCR provider returned no text.');
        }

        return $markdown;
    }
}
