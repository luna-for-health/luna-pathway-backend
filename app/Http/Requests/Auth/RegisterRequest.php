<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
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
             * If provided, updates this existing user instead of creating a new one.
             *
             * @example 1
             */
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            /**
             * @example Jane
             */
            'first_name' => ['required', 'string', 'max:255'],
            /**
             * @example Doe
             */
            'last_name' => ['required', 'string', 'max:255'],
            /**
             * @example jane.doe@example.com
             */
            'email' => [
                'required', 'string', 'max:255', 'email',
                Rule::unique('users', 'email')->ignore($this->input('user_id')),
            ],
            /**
             * @example +21611223344
             */
            'phone' => ['nullable', 'string', 'max:30'],
            /**
             * @example 1990-05-20
             */
            'birthdate' => ['nullable', 'date', 'before:today'],
            /**
             * @example Paris, France
             */
            'location' => ['nullable', 'string', 'max:255'],
            /**
             * Index into the client-side avatar catalog. Defaults to 1.
             *
             * @example 3
             */
            'avatar' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
