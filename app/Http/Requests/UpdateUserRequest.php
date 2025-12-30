<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
            'dni' => [
                'required',
                'string',
                // Rule::unique('users', 'dni')->ignore($this->route('id')),
            ],
            'name' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'birthday' => 'nullable|date',
            'gender_id' => 'required|integer',
            'office_id' => 'required|integer',
            'cde_id' => 'required|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'dni.required' => 'El DNI es obligatorio.',
            'dni.unique' => 'El DNI ya está en uso por otro usuario.',
            'name.required' => 'El nombre es obligatorio.',
            'lastname.required' => 'El apellido es obligatorio.',
            'birthday.required' => 'La fecha de nacimiento es obligatoria.',
        ];
    }
}
