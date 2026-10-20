<?php

namespace App\Http\Controllers\Api\Appointment;

use App\Http\Controllers\Controller;
use App\Models\ConsultationDocument;
use App\Models\Secretary;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Group('Appointments')]
class ConsultationDocumentController extends Controller
{
    /**
     * Download a consultation document
     *
     * Decrypts and streams the original file. Available to the staff (any document) and
     * to the patient who owns the consultation request it belongs to (their own only) —
     * any other patient gets a 403.
     */
    public function download(Request $request, ConsultationDocument $consultationDocument): StreamedResponse
    {
        $user = $request->user();
        $isOwner = ! $user instanceof Secretary
            && $consultationDocument->consultationRequest->user_id === $user->id;

        abort_unless($user instanceof Secretary || $isOwner, 403);

        $path = 'public/consultation-documents/'.$consultationDocument->path;

        abort_unless(Storage::exists($path), 404);

        $contents = Crypt::decrypt(Storage::get($path));

        return response()->streamDownload(
            fn () => print($contents),
            $consultationDocument->original_name,
            ['Content-Type' => $consultationDocument->mime_type ?? 'application/octet-stream']
        );
    }
}
