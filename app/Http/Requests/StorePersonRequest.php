<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePersonRequest extends FormRequest
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
            'documentnumber' => 'required|string|max:20|unique:people,documentnumber',
            'lastname' => 'required|string|max:100',
            'middlename' => 'required|string|max:100',
            'name' => 'required|string|max:100',
            'country_id' => 'required|exists:countries,id',
            'phone' => 'required|string|max:9',
            'email' => 'nullable|email',
            'birthday' => 'nullable|date',
            'sick' => 'nullable', // o boolean si prefieres
            'city_id' => 'required|exists:cities,id',
            'province_id' => 'required|exists:provinces,id',
            'district_id' => 'required|exists:districts,id',
            'address' => 'required|string|max:255',
            'typedocument_id' => 'required|exists:typedocuments,id',
            'gender_id' => 'required|exists:genders,id',
            'hasSoon' => 'nullable',
            'user_id' => 'nullable|exists:users,id', // id quien lo registró
        ];
    }

    public function messages(): array
    {
        return [
            'documentnumber.required' => 'El número de documento es obligatorio',
            'documentnumber.unique' => 'El número de documento ya está registrado',
            'lastname.required' => 'El apellido paterno es obligatorio',
            'middlename.required' => 'El apellido materno es obligatorio',
            'name.required' => 'El nombre es obligatorio',
            'country_id.required' => 'El país es obligatorio',
            'country_id.exists' => 'El país seleccionado no es válido',
            'phone.required' => 'El teléfono es obligatorio',
            'birthday.required' => 'La fecha de nacimiento es obligatoria',
            'birthday.before' => 'La fecha de nacimiento debe ser anterior a hoy',
            'sick.required' => 'El campo enfermedad es obligatorio',
            'sick.in' => 'El valor de enfermedad debe ser SI o NO',
            'city_id.required' => 'La ciudad es obligatoria',
            'city_id.exists' => 'La ciudad seleccionada no es válida',
            'province_id.required' => 'La provincia es obligatoria',
            'province_id.exists' => 'La provincia seleccionada no es válida',
            'district_id.required' => 'El distrito es obligatorio',
            'district_id.exists' => 'El distrito seleccionado no es válido',
            'address.required' => 'La dirección es obligatoria',
            'typedocument_id.required' => 'El tipo de documento es obligatorio',
            'typedocument_id.exists' => 'El tipo de documento seleccionado no es válido',
            'gender_id.required' => 'El género es obligatorio',
            'gender_id.exists' => 'El género seleccionado no es válido',
            'hasSoon.required' => 'El campo tiene hijo es obligatorio',
            'hasSoon.in' => 'El valor de tiene hijo debe ser SI o NO',
            'from_id.required' => 'El origen es obligatorio',
            'from_id.exists' => 'El origen seleccionado no es válido',
        ];
    }
}
