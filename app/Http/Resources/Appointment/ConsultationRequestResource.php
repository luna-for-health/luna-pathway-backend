<?php

namespace App\Http\Resources\Appointment;

use App\Http\Resources\UserResource;
use App\Models\ConsultationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ConsultationRequest
 */
class ConsultationRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reason' => $this->reason,
            'history' => $this->history,
            'dysmenorrhea' => $this->dysmenorrhea,
            'pelvicPain' => $this->pelvic_pain,
            'dyspareunia' => $this->dyspareunia,
            'digestiveIssues' => $this->digestive_issues,
            'urinaryIssues' => $this->urinary_issues,
            'fatigue' => $this->fatigue,
            'hasMri' => $this->has_mri,
            'status' => $this->status->value,
            'statusReason' => $this->status_reason,
            // Only present when the 'user' relation is eager loaded (e.g. the staff-facing
            // listing, which spans multiple patients) — omitted on the patient's own index,
            // which already returns their profile once at the top level.
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'documents' => $this->documents->map(fn ($document) => [
                'id' => $document->id,
                'originalName' => $document->original_name,
                'ocrText' => $document->ocr_text,
                'ocrFullText' => $document->ocr_full_text,
                'url' => route('consultationDocuments.download', $document),
            ]),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
