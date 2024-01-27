<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class StoreAuthRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // $user = Auth::user();
        // return $user != null && $user->tokenCan('super');
        return true;
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
        // return [
        //     'nickName' => 'required|string|max:255',
        //     'password' => 'required|string|min:8',
        //     'documentType' => '',
        //     'documentNumber' => '',
        //     'lastName' => 'required',
        //     'middleName' => 'required',
        //     'name' => 'required',
        //     'countryCode' => '',
        //     'email' => 'string|max:255|email',
        //     'officeCode' => 'required',
        //     'sedeCode' => 'required',
        //     'role' => 'required',
        //     'birthdate' => '',
        //     'gender' => '',
        //     'isDisabled' => '',
        //     'phoneNumber' => '',
        // ];
    }

    // protected function prepareForValidation()
    // {
    //     $this->merge([
    //         'nick_name' => $this->nickName,
    //         'document_type' => $this->documentType,
    //         'document_number' => $this->documentNumber,
    //         'last_name' => $this->lastName,
    //         'middle_name' => $this->middleName,
    //         'country_code' => $this->countryCode,
    //         'is_disabled' => $this->isDisabled,
    //         'phone_number' => $this->phoneNumber,
    //         'office_code' => $this->OfficeCode,
    //         'sede_code' => $this->sedeCode,
    //     ]);
    // }
}
