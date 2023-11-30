<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkStoreInvoicesRequest extends FormRequest
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
            '*.customerId' => ['required', 'integer'],
            '*.amount' => ['required', 'numeric'],
            '*.status' => ['required', Rule::in(['I', 'B', 'i', 'b'])],
            '*.birth_date' => ['required', 'date_format:Y-m-d H:i:s'],
            '*.paidDate' => ['date_format:Y-m-d H:i:s', 'nullable']
        ];
    }

    protected function prepareForValidation()
    {
        $data = [];
        foreach($this->toArray() as $obj) 
        {
            $obj['customer_id'] = $obj['customerId'] ?? null;
            $obj['birth_date'] = $obj['birthDate'] ?? null;
            $obj['paid_date'] = $obj['paidDate'] ?? null;
            $data[] = $obj;
        }
        $this->merge($data);
    }
}
