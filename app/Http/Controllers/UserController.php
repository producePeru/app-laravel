<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $perPage = 10;

        $users = User::with([
            'country' => function ($query) { $query->select('id', 'name'); },
            'sede' => function ($query) { $query->select('id', 'name'); }
        ])->select(
            'id', 
            'nick_name', 
            'document_type', 
            'document_number', 
            'last_name',
            'middle_name',
            'name', 
            'birthdate',
            'gender',
            'is_disabled',
            'phone_number',
            'office_code',
            'sede_code',
            'role',
            'email', 
            'country_code'
        )->paginate($perPage);

        return response()->json(['users' => $users]);
    }
}
