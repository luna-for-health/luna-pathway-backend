<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SendCodeRequest extends FormRequest
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
             * The email address to send the 6-digit code to.
             *
             * @example doctor@example.com
             */
            'email' => ['required', 'string', 'max:255', 'email'],
            /**
             * When "login", the endpoint 404s if no account exists for this email yet.
             * When "signup" (or omitted), any email is accepted.
             *
             * @example signup
             */
            'type' => ['nullable', 'string', 'in:login,signup'],
        ];
    }
}
