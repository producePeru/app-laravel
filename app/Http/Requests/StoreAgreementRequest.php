<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAgreementRequest extends FormRequest
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
            'office' => ['required'],
            'nameInstitution' => ['required'],
            'component' => ['required'],
            'responsible' => ['required'],
            'representative' => ['required'],
            'representativeEmail' => ['required', 'email'],
            'addendum' => ['required'],
            'proponent' => ['required'],
            'nameAgreement' => ['required'],
            'focalPoint' => ['required'],
            'phoneContact' => ['required'],
            'pdfDocument' => ['required'],
            'dateIssue' => ['required'],
            'effectiveDate' => ['required'],
            'dueDate' => ['required'],
            'created_by' => ['required'],
        ];
    }
}
