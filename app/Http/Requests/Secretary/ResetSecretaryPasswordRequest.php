<?php

namespace App\Http\Requests\Secretary;

use Illuminate\Foundation\Http\FormRequest;

class ResetSecretaryPasswordRequest extends FormRequest
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
             * @example admin@envast.tn
             */
            'email' => ['required', 'string', 'email'],
            /**
             * The 6-digit code received by email.
             *
             * @example 123456
             */
            'code' => ['required', 'string', 'size:6'],
            /**
             * @example new-secret-password
             */
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
