<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOnboardingRequest extends FormRequest
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
             * @example Jane
             */
            'first_name' => ['nullable', 'string', 'max:255'],
            /**
             * @example Doe
             */
            'last_name' => ['nullable', 'string', 'max:255'],
            /**
             * @example +21611223344
             */
            'phone' => ['nullable', 'string', 'max:30'],
            /**
             * Index into the client-side avatar catalog.
             *
             * @example 3
             */
            'avatar' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
