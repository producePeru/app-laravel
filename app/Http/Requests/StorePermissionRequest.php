<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Exceptions\CustomAuthorizationException; // Asegúrate de importar la clase
use Illuminate\Http\Request;
use App\Models\User;


class StorePermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(Request $request): bool
    {
   
        // $createdBy = $request->input('created');
        // $user = User::where('_id', $createdBy)->first();

        // return $user !== null && $user->role === 100;

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
            //
        ];
    }
}
