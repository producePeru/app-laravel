<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCyberwowParticipantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'ruc' => 'required|string|size:11',
            'razonSocial' => 'required|string|max:255',
            'nombreComercial' => 'nullable|string|max:255',

            'city_id' => 'required|exists:cities,id',
            'province_id' => 'required|exists:provinces,id',
            'district_id' => 'required|exists:districts,id',
            'direccion' => 'nullable|string|max:255',

            'economicsector_id' => 'required|exists:economicsectors,id',
            'comercialactivity_id' => 'required|exists:activities,id',
            'rubro_id' => 'required|exists:categories,id',
            'descripcion' => 'nullable|string',

            // 'socials' => 'nullable|array',
            // 'socials.*.name' => 'required_with:socials|string|max:100',
            // 'socials.*.link' => 'required_with:socials|string|max:255',

            'socials' => 'nullable|array',
            'socials.*.name' => 'nullable|string|max:100',
            'socials.*.link' => 'nullable|string|max:255',

            'typedocument_id' => 'required|exists:typedocuments,id',
            'documentnumber' => 'required|string|max:20',
            'lastname' => 'required|string|max:100',
            'middlename' => 'required|string|max:100',
            'name' => 'required|string|max:100',
            'gender_id' => 'required|exists:genders,id',

            'sick' => 'required|string|in:si,no',
            'phone' => 'required|string|regex:/^[0-9]{9,10}$/',
            'email' => 'required|email|max:150',
            'birthday' => 'required',
            'age' => 'required|integer|min:18|max:100',
            'country_id' => 'required|exists:countries,id',

            'question_1' => 'required|string',
            'question_2' => 'required|string',
            'question_3' => 'required|string',
            'question_4' => 'required|string',
            'question_5' => 'required|string',
            'question_6' => 'required|string',
            'question_7' => 'required|string',

            'howKnowEvent_id' => 'required|exists:propagandamedia,id',
            'autorization' => 'required|boolean',
        ];
    }
}
