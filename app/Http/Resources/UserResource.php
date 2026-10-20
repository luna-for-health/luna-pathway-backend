<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            /** @example 1 */
            'id' => $this->id,
            /** @example "Jane" */
            'first_name' => $this->first_name,
            /** @example "Doe" */
            'last_name' => $this->last_name,
            /** @example "jane.doe@example.com" */
            'email' => $this->email,
            /** @example "+21611223344" */
            'phone' => $this->phone,
            /** @example "1990-05-20" */
            'birthdate' => $this->birthdate?->toDateString(),
            /** @example "Paris, France" */
            'location' => $this->location,
            /** @example 1 */
            'avatar' => $this->avatar_id,
            'email_verified_at' => $this->email_verified_at,
            /** @example true */
            'has_accepted_gdpr' => $this->has_accepted_gdpr,
            /** @example true */
            'has_completed_onboarding' => $this->has_completed_onboarding,
        ];
    }
}
