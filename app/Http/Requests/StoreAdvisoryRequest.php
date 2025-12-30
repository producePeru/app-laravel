<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdvisoryRequest extends FormRequest
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
            'economicsector_id' => 'required|integer|exists:economicsectors,id',
            'comercialactivity_id' => 'required|integer|exists:comercialactivities,id',
            'observations' => 'nullable',
            'user_id' => 'nullable|integer|exists:users,id',
            'people_id' => 'required|integer',
            'component_id' => 'required|integer|exists:components,id',
            'theme_id' => 'required|integer',
            'modality_id' => 'required|integer|exists:modalities,id',
            'ruc' => 'nullable|max:11',
            'dni' => 'required|string|max:20',
            'city_id' => 'required|integer|exists:cities,id',
            'province_id' => 'required|integer|exists:provinces,id',
            'district_id' => 'required|integer|exists:districts,id',
            'cde_id' => 'nullable|integer|exists:cdes,id',
        ];
    }

    public function messages(): array
    {
        return [
            'economicsector_id.required' => 'El sector económico es obligatorio',
            'economicsector_id.integer' => 'El sector económico debe ser un número entero',
            'economicsector_id.exists' => 'El sector económico seleccionado no es válido',

            'comercialactivity_id.required' => 'La actividad comercial es obligatoria',
            'comercialactivity_id.integer' => 'La actividad comercial debe ser un número entero',
            'comercialactivity_id.exists' => 'La actividad comercial seleccionada no es válida',

            'observations.string' => 'Las observaciones deben ser texto',

            'user_id.required' => 'El usuario es obligatorio',
            'user_id.integer' => 'El usuario debe ser un número entero',
            'user_id.exists' => 'El usuario seleccionado no es válido',

            'people_id.required' => 'La persona es obligatoria',
            'people_id.integer' => 'La persona debe ser un número entero',
            'people_id.exists' => 'La persona seleccionada no es válida',

            'component_id.required' => 'El componente es obligatorio',
            'component_id.integer' => 'El componente debe ser un número entero',
            'component_id.exists' => 'El componente seleccionado no es válido',

            'theme_id.required' => 'El tema es obligatorio',
            'theme_id.integer' => 'El tema debe ser un número entero',
            'theme_id.exists' => 'El tema seleccionado no es válido',

            'modality_id.required' => 'La modalidad es obligatoria',
            'modality_id.integer' => 'La modalidad debe ser un número entero',
            'modality_id.exists' => 'La modalidad seleccionada no es válida',

            'ruc.string' => 'El RUC debe ser texto',
            'ruc.max' => 'El RUC no debe exceder 11 caracteres',

            'dni.required' => 'El DNI es obligatorio',
            'dni.string' => 'El DNI debe ser texto',
            'dni.max' => 'El DNI no debe exceder 20 caracteres',

            'city_id.required' => 'La ciudad es obligatoria',
            'city_id.integer' => 'La ciudad debe ser un número entero',
            'city_id.exists' => 'La ciudad seleccionada no es válida',

            'province_id.required' => 'La provincia es obligatoria',
            'province_id.integer' => 'La provincia debe ser un número entero',
            'province_id.exists' => 'La provincia seleccionada no es válida',

            'district_id.required' => 'El distrito es obligatorio',
            'district_id.integer' => 'El distrito debe ser un número entero',
            'district_id.exists' => 'El distrito seleccionado no es válido',

            'cde_id.integer' => 'El CDE debe ser un número entero',
            'cde_id.exists' => 'El CDE seleccionado no es válido',
        ];
    }
}
