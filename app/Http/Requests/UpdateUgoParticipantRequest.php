<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUgoParticipantRequest extends FormRequest
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
            'typedocument_id'    => 'required|integer',
            'documentnumber'     => 'required|string|max:12',
            'lastname'           => 'required|string|max:100',
            'middlename'         => 'nullable|string|max:100',
            'name'               => 'required|string|max:100',
            'gender_id'          => 'required|integer',
            'phone'              => 'nullable|string|regex:/^9\d{8}$/',
            'email'              => 'nullable|email|max:150',
            'ruc'                => 'nullable|string|size:11',
            'comercialName'      => 'nullable|string|max:150',
            'comercialActivity'  => 'nullable|string|max:100',
            'mercado'            => 'nullable|string|max:100',
            'sick'               => 'nullable'
        ];
    }
}
