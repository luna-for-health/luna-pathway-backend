<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateConsultationRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:255'],
            'history' => ['nullable', 'string'],
            'dysmenorrhea' => ['required', 'integer', 'between:0,10'],
            'pelvicPain' => ['required', 'integer', 'between:0,10'],
            'dyspareunia' => ['required', 'integer', 'between:0,10'],
            'digestiveIssues' => ['required', 'integer', 'between:0,10'],
            'urinaryIssues' => ['required', 'integer', 'between:0,10'],
            'fatigue' => ['required', 'integer', 'between:0,10'],
            'hasMri' => ['sometimes', 'boolean'],
            'documents' => ['sometimes', 'array'],
            'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:57344'],
        ];
    }
}
