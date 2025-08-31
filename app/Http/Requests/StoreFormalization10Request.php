<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFormalization10Request extends FormRequest
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
            'detailprocedure_id' => 'required|integer|exists:detailprocedures,id',
            'modality_id' => 'required|integer|exists:modalities,id',
            'economicsector_id' => 'required|integer|exists:economicsectors,id',
            'comercialactivity_id' => 'required|integer|exists:comercialactivities,id',
            'city_id' => 'required|integer|exists:cities,id',
            'province_id' => 'required|integer|exists:provinces,id',
            'district_id' => 'required|integer|exists:districts,id',
            'people_id' => 'required|integer',
            'address' => 'nullable|string|max:255',
            'ruc' => 'nullable|max:11',
            'user_id' => 'nullable|integer',
            'dni' => 'required|string|max:20',
            'cde_id' => 'nullable|integer|exists:cdes,id',
        ];
    }

    public function messages(): array
    {
        return [
            'detailprocedure_id.required' => 'El detalle del procedimiento es obligatorio',
            'detailprocedure_id.integer' => 'El detalle del procedimiento debe ser un número entero',
            'detailprocedure_id.exists' => 'El detalle del procedimiento seleccionado no es válido',

            'modality_id.required' => 'La modalidad es obligatoria',
            'modality_id.integer' => 'La modalidad debe ser un número entero',
            'modality_id.exists' => 'La modalidad seleccionada no es válida',

            'economicsector_id.required' => 'El sector económico es obligatorio',
            'economicsector_id.integer' => 'El sector económico debe ser un número entero',
            'economicsector_id.exists' => 'El sector económico seleccionado no es válido',

            'comercialactivity_id.required' => 'La actividad comercial es obligatoria',
            'comercialactivity_id.integer' => 'La actividad comercial debe ser un número entero',
            'comercialactivity_id.exists' => 'La actividad comercial seleccionada no es válida',

            'city_id.required' => 'La ciudad es obligatoria',
            'city_id.integer' => 'La ciudad debe ser un número entero',
            'city_id.exists' => 'La ciudad seleccionada no es válida',

            'province_id.required' => 'La provincia es obligatoria',
            'province_id.integer' => 'La provincia debe ser un número entero',
            'province_id.exists' => 'La provincia seleccionada no es válida',

            'district_id.required' => 'El distrito es obligatorio',
            'district_id.integer' => 'El distrito debe ser un número entero',
            'district_id.exists' => 'El distrito seleccionado no es válido',

            'people_id.required' => 'La persona es obligatoria',
            'people_id.integer' => 'La persona debe ser un número entero',
            'people_id.exists' => 'La persona seleccionada no es válida',

            'address.string' => 'La dirección debe ser texto',
            'address.max' => 'La dirección no debe exceder 255 caracteres',

            'ruc.string' => 'El RUC debe ser texto',
            'ruc.max' => 'El RUC no debe exceder 11 caracteres',

            'user_id.required' => 'El usuario es obligatorio',
            'user_id.integer' => 'El usuario debe ser un número entero',
            'user_id.exists' => 'El usuario seleccionado no es válido',

            'dni.required' => 'El DNI es obligatorio',
            'dni.string' => 'El DNI debe ser texto',
            'dni.max' => 'El DNI no debe exceder 20 caracteres',

            'cde_id.integer' => 'El CDE debe ser un número entero',
            'cde_id.exists' => 'El CDE seleccionado no es válido',
        ];
    }
}
