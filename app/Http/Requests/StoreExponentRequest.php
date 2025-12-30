<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExponentRequest extends FormRequest
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
            'documentType' => ['required', Rule::in(['dni', 'ce', 'pas', 'ptp'])],
            'documentNumber' => ['required'],
            'firstName' => ['required'],
            'lastName' => ['required'],
            'middleName' => ['required'],
            'gender' => ['required'],
            'email' => ['required', 'email'],
            'rucNumber' => [],
            'phoneNumber' => [],
            'specialty' => [],
            'profession' => [],
            'cvLink' => [],
            'user_id' => ['required'],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'document_type' => $this->documentType,
            'document_number' => $this->documentNumber,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'middle_name' => $this->middleName,
            'ruc_number' => $this->rucNumber,
            'phone_number' => $this->phoneNumber,
            'cv_link' => $this->cvLink
        ]);
    }
}
