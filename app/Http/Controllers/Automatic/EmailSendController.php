<?php

namespace App\Http\Controllers\Automatic;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessEmailQueue;
use Illuminate\Http\Request;

class EmailSendController extends Controller
{
    public function sendEmails(Request $request)
    {
        try {
            // Validación de los datos entrantes
            $validatedData = $request->validate([
                'emails' => 'required|array|min:1',
                'message' => 'required|string',
                'mailer' => 'nullable|string|in:gmail,office365',
            ]);

            $emails = $validatedData['emails'];
            $message = $validatedData['message'];
            $mailer = 'hostinger';

            // Procesar cada correo y enviarlo a la cola
            foreach ($emails as $email) {
                ProcessEmailQueue::dispatch($email, $message, $mailer);
            }

            return response()->json([
                'message' => 'Los correos se han enviado correctamente.',
                'status' => 200
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
                'status' => 422
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while queuing emails.',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }
}
