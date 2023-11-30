<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkshopRequest extends FormRequest
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
        $method = $this->method();

        if($method === 'PUT') {
            return [
                'title' => ['required'],
                'slug' => ['required'],
                'exponentId' => ['required'],
                'workshopDate' => ['required'],
                'typeIntervention' => ['required']
            ];
        } else {
            return [
                'title' => ['sometimes', 'required'],
                'slug' => ['sometimes', 'required'],
                'exponentId' => ['sometimes', 'required'],
                'workshopDate' => ['sometimes', 'required'],
                'typeIntervention' => ['sometimes', 'required']
            ];
        }
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'exponent_id' => $this->exponentId,
            'workshop_date' => $this->workshopDate,
            'type_intervention' => $this->typeIntervention
        ]);
    }
}
