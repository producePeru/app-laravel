<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkshopResource extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'exponentId' => $this->exponent_id,
            'workshopDate' => $this->workshop_date,
            'typeIntervention' => $this->type_intervention,
            'testinId' => $this->testin_id,
            'testoutId' => $this->testout_id,
            'invitationId' => $this->invitation_id,
            'status' => $this->status,
            'registered' => $this->registered,
            'link' => $this->link,
            'rrss' => $this->rrss,
            'sms' => $this->sms,
            'correo' => $this->correo,
            'userId' => $this->user_id
        ]
    }
}
