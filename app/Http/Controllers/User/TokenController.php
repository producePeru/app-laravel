<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Token;


class TokenController extends Controller
{
    public function index(Request $request)
    {
        $tokens = Token::orderBy('id', 'desc')->paginate(50);

        $tokens->getCollection()->transform(function ($token) {
            $token->status = $token->status == 0 ? false : true;
            return $token;
        });

        $activeToken = Token::where('status', 1)->orderBy('id', 'desc')->first();
        $id_active = $activeToken ? $activeToken->id : null;

        return response()->json(['data' => $tokens, 'id_active' => $id_active, 'status' => 200]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required|string|max:100',
            'name' => 'required|string|max:20',
        ]);

        $token = Token::create([
            'token' => $request->token,
            'name' => $request->name,
            'status' => 0,
            'count' => 0,
            'count_bad' => 0,
        ]);

        return response()->json(['message' => 'Registro exitoso', 'status' => 200]);
    }

    // public function updateStatus($id)
    // {
    //     Token::query()->update(['status' => 0]);

    //     $token = Token::findOrFail($id);
    //     $token->status = 1;
    //     $token->save();

    //     return response()->json(['message' => 'Status updated successfully', 'status' => 200]);
    // }

    // public function destroy($id)
    // {
    //     $token = Token::findOrFail($id);
    //     $token->delete();

    //     return response()->json(null, 204);
    // }
}
