<?php

namespace App\Http\Controllers\Api\Secretary\Appointment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\ListConsultationRequestsRequest;
use App\Http\Requests\Appointment\UpdateConsultationRequestStatusRequest;
use App\Http\Resources\Appointment\ConsultationRequestResource;
use App\Models\ConsultationRequest;
use App\Traits\ApiResponser;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

#[Group('Secretary')]
class ConsultationRequestController extends Controller
{
    use ApiResponser;

    /**
     * List every consultation request
     *
     * Staff-only. Returns consultation requests across all patients, oldest first,
     * each including the owning patient's profile. Filter by `status` (`reminder`,
     * `validated`, or `treated`); omit it to return requests in any status.
     */
    #[Response(200, 'Paginated list of consultation requests', type: 'array{status: true, message: string, data: ConsultationRequestResource[], pagination: array{perPage: int, total: int, page: int, lastPage: int, hasMorePages: bool}}')]
    public function index(ListConsultationRequestsRequest $request): JsonResponse
    {
        $paginator = ConsultationRequest::with(['documents', 'user'])
            ->when($request->validated('status'), fn ($query, $status) => $query->where('status', $status))
            ->oldest()
            ->oldest('id')
            ->paginate($request->validated('per_page') ?? 15);

        return $this->paginatedResponse(ConsultationRequestResource::collection($paginator), $paginator);
    }

    /**
     * Update a consultation request's status
     *
     * Staff-only. Moves a consultation request to `reminder`, `validated`, or `treated`,
     * optionally recording why (`status_reason`). Not scoped to a particular patient —
     * any staff member can update any request.
     */
    #[Response(200, 'Status updated', type: 'array{status: true, message: string, data: ConsultationRequestResource}')]
    public function updateStatus(UpdateConsultationRequestStatusRequest $request, ConsultationRequest $consultationRequest): JsonResponse
    {
        $consultationRequest->update([
            'status' => $request->validated('status'),
            'status_reason' => $request->validated('status_reason'),
        ]);

        return $this->successResponse(
            new ConsultationRequestResource($consultationRequest->load('documents')),
            __('appointments.status_updated')
        );
    }
}
