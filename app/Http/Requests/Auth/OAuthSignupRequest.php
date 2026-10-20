<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class OAuthSignupRequest extends FormRequest
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
             * The access token obtained from the provider's own SDK on the client side.
             *
             * @example ya29.a0AfH6SMC...
             */
            'access_token' => ['required', 'string'],
            /**
             * Overrides the provider's profile name if given (splitting the provider's
             * name is used otherwise). Requires last_name to also be set.
             *
             * @example Jane
             */
            'first_name' => ['nullable', 'string', 'max:255', 'required_with:last_name'],
            /**
             * @example Doe
             */
            'last_name' => ['nullable', 'string', 'max:255'],
            /**
             * @example +21611223344
             */
            'phone' => ['nullable', 'string', 'max:30'],
            /**
             * Index into the client-side avatar catalog. Defaults to 1.
             *
             * @example 3
             */
            'avatar' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
