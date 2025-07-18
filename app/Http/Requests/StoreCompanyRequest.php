<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
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

     public function rules()
    {
        return [
            'ruc' => 'required|string|max:11', // Validación para el RUC
            'razonSocial' => 'required|string|max:255',
            'sectorEconomico_id' => 'required|integer',
            'rubro_id' => 'required|integer',
            'actividadComercial_id' => 'required|integer',
            'region_id' => 'required|integer',
            'provincia_id' => 'required|integer',
            'distrito_id' => 'required|integer',
            'direccion' => 'required|string|max:255',
            'estado' => 'string|max:50',
            'condicion' => 'string|max:50',
        ];
    }


    public function messages()
    {
        return [
            'ruc.required' => 'El campo RUC es obligatorio.',
            'ruc.string' => 'El campo RUC debe ser una cadena de texto.',
            'ruc.max' => 'El campo RUC no puede tener más de 11 caracteres.',
            'ruc.regex' => 'El RUC debe ser un número de 11 dígitos.'
        ];
    }
}
