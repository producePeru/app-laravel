<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkshopRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required'],
            'slug' => ['required', 'unique:workshops'],
            'exponentId' => ['required'],
            'workshopDate'=> ['required'],
            'typeIntervention'=> ['required'], 
            'userId' => ['required'],
            'link' => [],
            
            'testinId'=> [],
            'testoutId'=> [],
            'invitationId'=> [],
            'status'=> [],
            'registered'=> [],
            'rrss'=> [],
            'sms'=> [],
            'correo'=> [],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'exponent_id' => $this->exponentId,
            'workshop_date' => $this->workshopDate,
            'type_intervention' => $this->typeIntervention,
            'user_id' => $this->userId,
            'testin_id' => $this->testinId,
            'testout_id' => $this->testoutId,
            'invitation_id' => $this->invitationId
        ]);
    }
}
