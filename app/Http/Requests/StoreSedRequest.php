<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSedRequest extends FormRequest
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
            'ruc' => 'required', // RUC debe ser único
            'comercialName' => 'required|string',
            'economicsector_id' => 'required|integer',
            'comercialactivity_id' => 'required|integer',
            'category_id' => 'required|integer',
            'city_id' => 'required|integer',
            'province_id' => 'required|integer',
            'district_id' => 'required|integer',
            'address' => 'required|string|max:255',
            'typeAsistente' => 'required',
            'documentnumber' => 'required',
            'sick' => 'required|string',
            'phone' => 'required',
            'email' => 'required|email|max:255',
            'positionCompany' => 'required|string|max:255',
            'howKnowEvent_id' => 'required|integer',
            'slug' => 'required|string',


            'age' => 'nullable',
        ];
    }

    public function messages()
    {
        return [
            'ruc.unique' => 'Este RUC ya está registrado.',
            'slug.unique' => 'Este evento con este slug ya existe.',
            // Agregar otros mensajes personalizados si es necesario
        ];
    }
}
