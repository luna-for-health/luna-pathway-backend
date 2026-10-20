<?php

namespace App\Http\Requests\Appointment;

use App\Enums\ConsultationRequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConsultationRequestStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            /**
             * @example validated
             */
            'status' => ['required', Rule::enum(ConsultationRequestStatus::class)],
            /**
             * Why the status is being changed.
             *
             * @example Patient confirmed by phone
             */
            'status_reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
