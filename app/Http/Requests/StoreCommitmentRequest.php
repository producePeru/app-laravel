<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommitmentRequest extends FormRequest
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
            'created' => ['required'],
            'idAgreement' => ['required'],
            'entity' => ['required'],
            'isMeta' => [],
            'unitMeasurement' => ['required_if:isMeta,true'],
            'metaNumb' => ['required_if:isMeta,true'],
            'description' => ['required'],
            'status' => []
        ];
    }
}
