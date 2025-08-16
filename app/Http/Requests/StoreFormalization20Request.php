<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFormalization20Request extends FormRequest
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
            'codesunarp' => 'nullable|string|max:50',
            'numbernotary' => 'required|string|max:50',
            'address' => 'required|string|max:255',
            'economicsector_id' => 'required|integer|exists:economicsectors,id',
            'comercialactivity_id' => 'required|integer|exists:comercialactivities,id',
            'regime_id' => 'required|integer|exists:regimes,id',
            'city_id' => 'required|integer|exists:cities,id',
            'province_id' => 'required|integer|exists:provinces,id',
            'district_id' => 'required|integer|exists:districts,id',
            'modality_id' => 'required|integer|exists:modalities,id',
            'notary_id' => 'required|integer|exists:notaries,id',
            'user_id' => 'nullable|integer',
            'people_id' => 'required|integer',
            'nameMype' => 'required|string|max:100',
            'dateReception' => 'nullable|date',
            'dateTramite' => 'nullable|date',
            'ruc' => 'nullable|max:11',
            'typecapital_id' => 'nullable|integer',
            'isbic' => 'nullable|string|in:SI,NO',
            'montocapital' => 'nullable|numeric|min:0',
            'cde_id' => 'nullable|integer|exists:cdes,id',
        ];
    }

    public function messages(): array
    {
        return [
            'codesunarp.string' => 'El código SUNARP debe ser texto',
            'codesunarp.max' => 'El código SUNARP no debe exceder 50 caracteres',

            'numbernotary.required' => 'El número de notaría es obligatorio',
            'numbernotary.string' => 'El número de notaría debe ser texto',
            'numbernotary.max' => 'El número de notaría no debe exceder 50 caracteres',

            'address.required' => 'La dirección es obligatoria',
            'address.string' => 'La dirección debe ser texto',
            'address.max' => 'La dirección no debe exceder 255 caracteres',

            'economicsector_id.required' => 'El sector económico es obligatorio',
            'economicsector_id.integer' => 'El sector económico debe ser un número entero',
            'economicsector_id.exists' => 'El sector económico seleccionado no es válido',

            'comercialactivity_id.required' => 'La actividad comercial es obligatoria',
            'comercialactivity_id.integer' => 'La actividad comercial debe ser un número entero',
            'comercialactivity_id.exists' => 'La actividad comercial seleccionada no es válida',

            'regime_id.required' => 'El régimen es obligatorio',
            'regime_id.integer' => 'El régimen debe ser un número entero',
            'regime_id.exists' => 'El régimen seleccionado no es válido',

            'city_id.required' => 'La ciudad es obligatoria',
            'city_id.integer' => 'La ciudad debe ser un número entero',
            'city_id.exists' => 'La ciudad seleccionada no es válida',

            'province_id.required' => 'La provincia es obligatoria',
            'province_id.integer' => 'La provincia debe ser un número entero',
            'province_id.exists' => 'La provincia seleccionada no es válida',

            'district_id.required' => 'El distrito es obligatorio',
            'district_id.integer' => 'El distrito debe ser un número entero',
            'district_id.exists' => 'El distrito seleccionado no es válido',

            'modality_id.required' => 'La modalidad es obligatoria',
            'modality_id.integer' => 'La modalidad debe ser un número entero',
            'modality_id.exists' => 'La modalidad seleccionada no es válida',

            'notary_id.required' => 'La notaría es obligatoria',
            'notary_id.integer' => 'La notaría debe ser un número entero',
            'notary_id.exists' => 'La notaría seleccionada no es válida',

            'user_id.required' => 'El usuario es obligatorio',
            'user_id.integer' => 'El usuario debe ser un número entero',
            'user_id.exists' => 'El usuario seleccionado no es válido',

            'people_id.required' => 'La persona es obligatoria',
            'people_id.integer' => 'La persona debe ser un número entero',
            'people_id.exists' => 'La persona seleccionada no es válida',

            'nameMype.required' => 'El nombre de la MYPE es obligatorio',
            'nameMype.string' => 'El nombre de la MYPE debe ser texto',
            'nameMype.max' => 'El nombre de la MYPE no debe exceder 100 caracteres',

            'dateReception.date' => 'La fecha de recepción debe ser una fecha válida',

            'dateTramite.date' => 'La fecha de trámite debe ser una fecha válida',

            'ruc.string' => 'El RUC debe ser texto',
            'ruc.max' => 'El RUC no debe exceder 11 caracteres',

            'dni.required' => 'El DNI es obligatorio',
            'dni.string' => 'El DNI debe ser texto',
            'dni.max' => 'El DNI no debe exceder 20 caracteres',

            'typecapital_id.integer' => 'El tipo de capital debe ser un número entero',
            'typecapital_id.exists' => 'El tipo de capital seleccionado no es válido',

            'isbic.string' => 'El campo ISBIC debe ser texto',
            'isbic.in' => 'El valor de ISBIC debe ser SI o NO',

            'montocapital.numeric' => 'El monto de capital debe ser un número',
            'montocapital.min' => 'El monto de capital no puede ser negativo',

            'cde_id.integer' => 'El CDE debe ser un número entero',
            'cde_id.exists' => 'El CDE seleccionado no es válido',
        ];
    }
}
