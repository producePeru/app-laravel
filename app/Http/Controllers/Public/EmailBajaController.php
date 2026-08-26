<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\EmailCancelado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmailBajaController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'motivo' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $emailCancelado = EmailCancelado::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Correo registrado correctamente.',
            'data' => $emailCancelado,
        ], 201);
    }
}
