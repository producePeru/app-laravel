<?php

namespace App\Http\Controllers\Disclaimer;

use App\Http\Controllers\Controller;
use App\Mail\DisclaimerMail;
use App\Models\CyberwowDisclainer;
use App\Models\CyberwowParticipant;
use App\Models\Fair;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DisclaimerController extends Controller
{
    /**
     * Trae la descripción de la declaración jurada según el slug del evento.
     * GET events-pnte/disclaimer-cw/{slug} (público)
     */
    public function getDisclaimerBySlug($slug)
    {
        $fair = Fair::where('slug', $slug)->first();

        if (! $fair) {
            return response()->json([
                'status' => 404,
                'message' => 'Evento no encontrado',
                'data' => null,
            ]);
        }

        $disclaimer = CyberwowDisclainer::where('id_fair', $fair->id)->first();

        return response()->json([
            'status' => 200,
            'data' => $disclaimer,
        ]);
    }

    public function saveDisclaimer(Request $request)
    {
        try {
            $mailer = 'hostinger3k';

            // ✅ VALIDACIÓN COMPLETA (incluye data del PDF)
            $validated = $request->validate([
                'slug' => 'required|string',
                'dni' => 'required',
                'ruc' => 'required|digits:11',
                'nombre' => 'required|string',
                'empresa' => 'required|string',
                'correo' => 'required|email',
                'fecha' => 'required',
            ]);

            // 🔍 EVENTO
            $fair = Fair::where('slug', $validated['slug'])->firstOrFail();

            // 🔍 PARTICIPANTE
            $participant = CyberwowParticipant::where('event_id', $fair->id)
                ->where('documentnumber', $validated['dni'])
                ->where('ruc', $validated['ruc'])
                ->first();

            if (! $participant) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Participante no encontrado',
                ]);
            }

            // ✅ GUARDAR DISCLAIMER
            $participant->disclaimer = 1;
            $participant->save();

            // =========================================
            // 📄 GENERAR PDF CON LA DATA DEL FORM
            // La descripción es dinámica: viene de cyberwow_disclainer
            // según el evento (id_fair).
            // =========================================
            $disclainer = CyberwowDisclainer::where('id_fair', $fair->id)->first();

            $pdf = Pdf::loadView('pdf.disclaimer', [
                'data' => $validated,
                'descripcion' => $disclainer?->descripcion,
            ])->output();

            // =========================================
            // 📧 ENVIAR CORREO + COPIA
            // El envío no debe tumbar el registro: si el SMTP falla,
            // se responde éxito con advertencia y se registra en el log.
            // =========================================
            $mailSent = false;
            $mailError = null;

            try {
                Mail::mailer($mailer)
                    ->to($validated['correo']) // usuario
                    ->cc('feriascomerciales.pnte@produce.gob.pe') // copia
                    ->send(new DisclaimerMail($pdf, $validated));

                $mailSent = true;
            } catch (\Exception $mailException) {
                $mailError = $mailException->getMessage();

                Log::error('Error al enviar correo de declaración jurada', [
                    'mailer' => $mailer,
                    'to' => $validated['correo'],
                    'ruc' => $validated['ruc'],
                    'slug' => $validated['slug'],
                    'error' => $mailError,
                ]);
            }

            if (! $mailSent) {
                return response()->json([
                    'status' => 200,
                    'mail_sent' => false,
                    'message' => 'Declaración registrada y PDF generado, pero no se pudo enviar el correo. Revisa la configuración SMTP del mailer "'.$mailer.'".',
                    'mail_error' => $mailError,
                ]);
            }

            return response()->json([
                'status' => 200,
                'mail_sent' => true,
                'message' => 'PDF generado y enviado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error en el servidor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
