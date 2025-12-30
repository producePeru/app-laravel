<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateFairRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'subTitle' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'fairtype_id' => 'required|integer|exists:fairtypes,id',
            'modality_id' => 'required|integer|exists:modalities,id',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
            'metaMypes' => 'nullable|integer|min:1',
            'city_id' => 'required|integer|exists:cities,id',
            'fecha' => 'required|string|max:100',
            'place' => 'required|string|max:200',
            'hours' => 'required|string|max:100',
            'msgEndForm' => 'nullable|string',
            'msgSendEmail' => 'nullable|string',
            'image_id' => 'nullable|integer|exists:images,id',
        ];
    }
}
