<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
            'dni' => ['required', 'string', 'unique:users,dni'],
            'name' => ['required', 'string', 'max:100'],
            'lastname' => ['required', 'string', 'max:100'],
            'middlename' => ['nullable', 'string', 'max:100'],
            'birthday' => ['required', 'date'],
            'gender_id' => ['required', 'integer'],
            'office_id' => ['required', 'integer'],
            'cde_id' => ['required', 'integer'],
            'phone' => ['nullable', 'string', 'max:9'],
            'rol' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'dni.required' => 'El DNI es obligatorio.',
            'dni.unique' => 'Este DNI ya está registrado.',
            'name.required' => 'El nombre es obligatorio.',
            'lastname.required' => 'El apellido es obligatorio.',
            'birthday.required' => 'La fecha de nacimiento es obligatoria.',
            'gender_id.required' => 'Debe seleccionar el género.',
            'office_id.required' => 'Debe seleccionar la oficina.',
            'cde_id.required' => 'Debe seleccionar el CDE.',
        ];
    }
}
