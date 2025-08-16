<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Page;

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


if (!function_exists('getPermission')) {
    function getPermission($slug)
    {
        $user = Auth::user(); // ✅ Obtenemos el modelo completo del usuario

        if (!$user) {
            return ['hasPermission' => false];
        }

        // ✅ Buscamos la página usando el slug en la tabla correcta
        $page = Page::where('slug', $slug)->first();

        if (!$page) {
            return ['hasPermission' => false];
        }

        // ✅ Verificamos si el usuario tiene relación con esa página
        $pivot = $user->pages()->where('page_id', $page->id)->first()?->pivot;


        if (!$pivot) {
            return ['hasPermission' => false];
        }

        return [
            'hasPermission' => $pivot->can_view_all == 1 ? true : false,
        ];
    }
}
