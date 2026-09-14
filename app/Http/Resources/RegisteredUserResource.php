<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property User $resource
 */
final class RegisteredUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->uuid,
            'email' => $this->resource->email,
            'status' => $this->resource->status->value,
            'email_verified_at' => $this->resource->email_verified_at?->format(DATE_ATOM),
        ];
    }
}
