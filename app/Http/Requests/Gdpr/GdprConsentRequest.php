<?php

namespace App\Http\Requests\Gdpr;

use Illuminate\Foundation\Http\FormRequest;

class GdprConsentRequest extends FormRequest
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
            'email' => ['required', 'string', 'max:255', 'email'],
            'type' => ['nullable', 'string', 'in:login,signup'],
        ];
    }
}
