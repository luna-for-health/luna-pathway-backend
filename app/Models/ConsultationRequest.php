<?php

namespace App\Models;

use App\Enums\ConsultationRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsultationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reason',
        'history',
        'status',
        'status_reason',
        'dysmenorrhea',
        'pelvic_pain',
        'dyspareunia',
        'digestive_issues',
        'urinary_issues',
        'fatigue',
        'has_mri',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ConsultationRequestStatus::class,
            'has_mri' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ConsultationDocument::class);
    }
}
