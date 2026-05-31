<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'first_name'       => $this->first_name,
            'last_name'        => $this->last_name,
            'full_name'        => $this->first_name . ' ' . $this->last_name,
            'email'            => $this->email,
            'phone'            => $this->phone,
            'date_of_birth'    => $this->date_of_birth,
            'education_level'  => $this->education_level,
            'avatar_url' => $this->avatar_url ? asset('storage/' . $this->avatar_url) : asset('storage/avatars/default-avatar.png'),
        ];
    }
}
