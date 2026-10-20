<?php

namespace App\Services\Appointment;

use App\Enums\ConsultationRequestStatus;
use App\Jobs\ExtractConsultationDocumentOcr;
use App\Models\ConsultationRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class ConsultationRequestService
{
    /**
     * @param  array{reason: string, history?: string|null, dysmenorrhea: int, pelvicPain: int, dyspareunia: int, digestiveIssues: int, urinaryIssues: int, fatigue: int, hasMri?: bool}  $data
     * @param  UploadedFile[]|null  $files
     */
    public static function create(array $data, ?array $files, User $user): ConsultationRequest
    {
        $consultationRequest = ConsultationRequest::create([
            'user_id' => $user->id,
            'reason' => $data['reason'],
            'history' => $data['history'] ?? null,
            'status' => ConsultationRequestStatus::New,
            'dysmenorrhea' => $data['dysmenorrhea'],
            'pelvic_pain' => $data['pelvicPain'],
            'dyspareunia' => $data['dyspareunia'],
            'digestive_issues' => $data['digestiveIssues'],
            'urinary_issues' => $data['urinaryIssues'],
            'fatigue' => $data['fatigue'],
            'has_mri' => $data['hasMri'] ?? false,
        ]);

        foreach ($files ?? [] as $file) {
            $document = $consultationRequest->documents()->create(self::storeDocument($file));

            ExtractConsultationDocumentOcr::dispatch($document);
        }

        return $consultationRequest->load('documents');
    }

    /**
     * @return array{original_name: string, path: string, mime_type: string|null}
     */
    public static function storeDocument(UploadedFile $file): array
    {
        $originalName = $file->getClientOriginalName();
        $hashedName = md5($originalName.date('his')).'.'.$file->getClientOriginalExtension();

        Storage::put('public/consultation-documents/'.$hashedName, Crypt::encrypt($file->getContent()));

        return [
            'original_name' => $originalName,
            'path' => $hashedName,
            'mime_type' => $file->getClientMimeType(),
        ];
    }
}
