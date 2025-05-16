<?php

namespace App\Http\Controllers\Captcha;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CaptchaController extends Controller
{
    public function verify(Request $request)
    {
        // Obtener el token de reCAPTCHA desde la solicitud
        $recaptchaToken = $request->input('recaptcha_token');

        // Verificar el token con Google
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => env('RECAPTCHA_SECRET_KEY'),
            'response' => $recaptchaToken,
        ]);

        $data = $response->json();

        // Verificar si la validación fue exitosa
        if (!$data['success']) {
            return response()->json(['message' => 'Error de reCAPTCHA'], 400);
        }

        // Si es válido, continúa con el procesamiento
        return response()->json(['message' => 'Formulario validado correctamente', 'status' => 200]);
    }
}
