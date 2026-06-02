<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TareaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'titulo'  => 'required|string|max:255',
            'unidad'  => 'required|in:UGSE,UGO,UGGER,COOPERATIVAS,DE,COMUNICACIONES',
            'detalle' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'El título es obligatorio.',
            'unidad.required' => 'La unidad es obligatoria.',
            'unidad.in'       => 'La unidad seleccionada no es válida.',
        ];
    }
}
