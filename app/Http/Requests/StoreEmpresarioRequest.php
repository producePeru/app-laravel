<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmpresarioRequest extends FormRequest
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
            'typedocument_id' => 'required|integer',
            'dni' => 'required|string|max:15',
            'name' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'gender_id' => 'required|integer',
            'birthday' => 'required|date',
            'phone' => 'required|string|max:15',
        ];
    }


    public function messages()
    {
        return [
            'dni.unique' => 'El DNI ya está registrado.',
            // Puedes agregar otros mensajes personalizados si lo necesitas
        ];
    }
}
