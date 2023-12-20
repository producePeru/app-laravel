<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMypeRequest extends FormRequest
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
            'ruc' => ['required'],
            'socialReason' => ['required'],
            'category' => ['required'],
            'type' => ['required'],
            'department' => [],
            'district' => [],
            'nameComplete' => [],
            'dniNumber' => ['required'],
            'sex' => [],
            'phone' => [],
            'email' => [],
            'added' => []
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'social_reason' => $this->socialReason,
            'name_complete' => $this->nameComplete,
            'dni_number' => $this->dniNumber
        ]);
    }
}
