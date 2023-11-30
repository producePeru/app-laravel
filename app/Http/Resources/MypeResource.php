<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ruc' => $this->ruc,
            'socialReason' => $this->social_reason,
            'category' => $this->category,
            'type' => $this->type,
            'department' => $this->department,
            'district' => $this->district,
            'nameComplete'  => $this->name_complete,
            'dniNumber' => $this->dni_number,
            'sex' => $this->sex,
            'phone' => $this->phone,
            'email' => $this->email
        ];
    }
}
