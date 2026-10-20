<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyCodeRequest extends FormRequest
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
             * The email address the code was sent to.
             *
             * @example doctor@example.com
             */
            'email' => ['required', 'string', 'max:255', 'email'],
            /**
             * The 6-digit code received by email.
             *
             * @example 123456
             */
            'code' => ['required', 'string', 'size:6'],
        ];
    }
}
