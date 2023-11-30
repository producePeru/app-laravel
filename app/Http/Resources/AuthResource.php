<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
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
            'nickName' => $this->nick_name,
            'password' => $this->type,
            'documentType' => $this->document_type,
            'documentNumber' => $this->document_number,
            'lastName' => $this->last_name,
            'middleName' => $this->middle_name,
            'name' => $this->name,
            'countryCode' => $this->country_code,
            'birthdate' => $this->birthdate,
            'gender' => $this->gender,
            'isDisabled' => $this->is_disabled,
            'email' => $this->email,
            'phoneNumber' => $this->phone_number,
            'OfficeCode' => $this->office_code,
            'sedeCode' => $this->sede_code,
            'role' => $this->role,

           
        ];
    }
}
