<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SedQuestionStoreRequest extends FormRequest
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
            'slug'           => ['required', 'string', 'exists:fairs,slug'],
            'documentnumber' => ['required', 'string', 'max:12'],
            'question_1'     => ['nullable', 'string'],
            'question_2'     => ['nullable', 'string'],
            'question_3'     => ['nullable', 'string'],
            'question_4'     => ['nullable', 'string'],
            'question_5'     => ['nullable', 'string'],
        ];
    }
}
