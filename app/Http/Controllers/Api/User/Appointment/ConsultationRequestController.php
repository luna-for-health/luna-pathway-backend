<?php

namespace App\Http\Controllers\Api\User\Appointment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\CreateConsultationRequestRequest;
use App\Http\Requests\Appointment\ListConsultationRequestsRequest;
use App\Http\Resources\Appointment\ConsultationRequestResource;
use App\Http\Resources\UserResource;
use App\Models\ConsultationRequest;
use App\Models\User;
use App\Services\Appointment\ConsultationRequestService;
use App\Services\Mail\BrevoMail;
use App\Traits\ApiResponser;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

#[Group('Appointments')]
class ConsultationRequestController extends Controller
{
    use ApiResponser;

    /**
     * List the authenticated user's consultation requests
     *
     * Returns the caller's own consultation requests, oldest first. Filter by
     * `status` (`reminder`, `validated`, or `treated`); omit it to return requests in any status.
     */
    #[Response(200, 'Paginated list of consultation requests', type: 'array{status: true, message: string, data: array{user: UserResource, consultationRequests: ConsultationRequestResource[]}, pagination: array{perPage: int, total: int, page: int, lastPage: int, hasMorePages: bool}}')]
    public function index(ListConsultationRequestsRequest $request): JsonResponse
    {
        $user = $request->user();

        $paginator = $user->consultationRequests()
            ->with('documents')
            ->when($request->validated('status'), fn ($query, $status) => $query->where('status', $status))
            ->oldest()
            ->oldest('id')
            ->paginate($request->validated('per_page') ?? 15);

        return $this->paginatedResponse([
            'user' => new UserResource($user),
            'consultationRequests' => ConsultationRequestResource::collection($paginator),
        ], $paginator);
    }

    /**
     * Start booking an appointment with a consultation request
     *
     * Creates a consultation request containing the reason for the visit, the patient's
     * history, and any supporting documents. Each uploaded document is stored encrypted
     * at rest and queued for OCR text extraction (`ocrText` is null until the background
     * job completes). A confirmation email is then queued to the patient.
     */
    #[Response(200, 'Consultation request created', type: 'array{status: true, message: string, data: ConsultationRequestResource}')]
    public function store(CreateConsultationRequestRequest $request): JsonResponse
    {
        $consultationRequest = ConsultationRequestService::create(
            $request->validated(),
            $request->file('documents'),
            $request->user()
        );

        $this->sendCreatedConfirmation($request->user(), $consultationRequest);

        return $this->successResponse(new ConsultationRequestResource($consultationRequest), __('appointments.created'));
    }

    /**
     * Queue the Brevo confirmation email. Skipped when no template is configured or the patient has no email.
     */
    private function sendCreatedConfirmation(User $user, ConsultationRequest $consultationRequest): void
    {
        $templateId = config('services.brevo.templates.consultation_request_created');

        if (blank($templateId) || blank($user->email)) {
            return;
        }

        BrevoMail::to($user->email)
            ->template((int) $templateId, [
                'PRENOM' => $user->last_name,
                'lastName' => $user->last_name,
                'firstName' => $user->first_name,
                'consultationRequestRef' => $consultationRequest->id,
                'createdAt' => $consultationRequest->created_at->format('d/m/Y H:i'),
            ])
            ->tags('consultation-request-created')
            ->queue();
    }
}
