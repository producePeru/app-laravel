<?php

function getUserRole()
{
    $user_id = Auth::user()->id;

    $roleUser = DB::table('role_user')
    ->where('user_id', $user_id)
    ->pluck('role_id')
    ->unique()
    ->toArray();

    return [
        "role_id" => $roleUser,
        'user_id' => $user_id
    ];
}
