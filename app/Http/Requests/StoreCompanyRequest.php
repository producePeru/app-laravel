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
            'socialReason' => 'required|string|max:255',
            'economicsector_id' => 'required|integer',
            'category_id' => 'required|integer',
            'comercialactivity_id' => 'required|integer',
            'city_id' => 'required|integer',
            'province_id' => 'required|integer',
            'district_id' => 'required|integer',
            'address' => 'required|string|max:255',
            'estado' => 'nullable|string|max:50',
            'condicion' => 'nullable|string|max:50',
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
