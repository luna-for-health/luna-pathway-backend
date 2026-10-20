<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationDocument extends Model
{
    use HasFactory;

    protected $fillable = ['consultation_request_id', 'original_name', 'path', 'mime_type', 'ocr_text', 'ocr_full_text'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ocr_text' => 'array',
        ];
    }

    public function consultationRequest(): BelongsTo
    {
        return $this->belongsTo(ConsultationRequest::class);
    }
}
