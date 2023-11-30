<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTestinRequest extends FormRequest
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
        $method = $this->method();

        if($method === 'PUT') {
            return [
                'date_end' => ['required'],

                'question1' => ['required'],
                'question1_opt1' => ['required'],
                'question1_opt2' => ['required'],
                'question1_opt3' => ['required'],
                'question1_resp' => ['required'],

                'question2' => ['required'],
                'question2_opt1' => ['required'],
                'question2_opt2' => ['required'],
                'question2_opt3' => ['required'],
                'question2_resp' => ['required'],

                'question3' => ['required'],
                'question3_opt1' => ['required'],
                'question3_opt2' => ['required'],
                'question3_opt3' => ['required'],
                'question3_resp' => ['required'],

                'question4' => ['required'],
                'question4_opt1' => ['required'],
                'question4_opt2' => ['required'],
                'question4_opt3' => ['required'],
                'question4_resp' => ['required'],

                'question5' => ['required'],
                'question5_opt1' => ['required'],
                'question5_opt2' => ['required'],
                'question5_opt3' => ['required'],
                'question5_resp' => ['required'],
                
                'workshop_id' => ['required']
            ];
        } else {
            return [
                'date_end' => ['sometimes', 'required'],

                'question1' => ['sometimes', 'required'],
                'question1_opt1' => ['sometimes', 'required'],
                'question1_opt2' => ['sometimes', 'required'],
                'question1_opt3' => ['sometimes', 'required'],
                'question1_resp' => ['sometimes', 'required'],

                'question2' => ['sometimes', 'required'],
                'question2_opt1' => ['sometimes', 'required'],
                'question2_opt2' => ['sometimes', 'required'],
                'question2_opt3' => ['sometimes', 'required'],
                'question2_resp' => ['sometimes', 'required'],

                'question3' => ['sometimes', 'required'],
                'question3_opt1' => ['sometimes', 'required'],
                'question3_opt2' => ['sometimes', 'required'],
                'question3_opt3' => ['sometimes', 'required'],
                'question3_resp' => ['sometimes', 'required'],

                'question4' => ['sometimes', 'required'],
                'question4_opt1' => ['sometimes', 'required'],
                'question4_opt2' => ['sometimes', 'required'],
                'question4_opt3' => ['sometimes', 'required'],
                'question4_resp' => ['sometimes', 'required'],

                'question5' => ['sometimes', 'required'],
                'question5_opt1' => ['sometimes', 'required'],
                'question5_opt2' => ['sometimes', 'required'],
                'question5_opt3' => ['sometimes', 'required'],
                'question5_resp' => ['sometimes', 'required'],

                'workshop_id' => ['sometimes', 'required']
            ];
        }
    }
}
