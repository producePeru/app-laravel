<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExponentResource extends JsonResource
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
            'documentType' => $this->document_type,
            'documentNumber' => $this->document_number,
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'middleName' => $this->middle_name,
            'email' => $this->email,
            'rucNumber' => $this->ruc_number,
            'phoneNumber' => $this->phone_number,
            'specialty' => $this->specialty,
            'profession' => $this->profession,
            'cvLink' => $this->cv_link
        ];
    }
}
