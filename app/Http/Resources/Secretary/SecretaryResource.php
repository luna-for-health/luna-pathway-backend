<?php

namespace App\Http\Resources\Secretary;

use App\Models\Secretary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Secretary
 */
class SecretaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            /** @example 1 */
            'id' => $this->id,
            /** @example "Admin" */
            'name' => $this->name,
            /** @example "admin@envast.tn" */
            'email' => $this->email,
            'roles' => $this->roles->pluck('name'),
        ];
    }
}
