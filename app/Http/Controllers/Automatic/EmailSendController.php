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
            ]);

            $emails = $validatedData['emails'];
            $message = $validatedData['message'];

            // Procesar cada correo y enviarlo a la cola
            foreach ($emails as $email) {
                ProcessEmailQueue::dispatch($email, $message);
            }

            return response()->json([
                'message' => 'Emails queued for sending.',
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
