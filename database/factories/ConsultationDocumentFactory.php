<?php

namespace Database\Factories;

use App\Models\ConsultationDocument;
use App\Models\ConsultationRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsultationDocument>
 */
class ConsultationDocumentFactory extends Factory
{
    protected $model = ConsultationDocument::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'consultation_request_id' => ConsultationRequest::factory(),
            'original_name' => fake()->word().'.pdf',
            'path' => fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'ocr_text' => null,
            'ocr_full_text' => null,
        ];
    }
}
