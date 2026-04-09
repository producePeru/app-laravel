<?php

namespace App\Http\Controllers\Disclaimer;

use App\Http\Controllers\Controller;
use App\Models\CyberwowParticipant;
use App\Models\Fair;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Mail\DisclaimerMail;

class DisclaimerController extends Controller
{
    public function saveDisclaimer(Request $request)
    {
        try {
            $mailer = 'cyberpnte';

            // ✅ VALIDACIÓN COMPLETA (incluye data del PDF)
            $validated = $request->validate([
                'slug'    => 'required|string',
                'dni'     => 'required',
                'ruc'     => 'required|digits:11',
                'nombre'  => 'required|string',
                'empresa' => 'required|string',
                'correo'  => 'required|email',
                'fecha'   => 'required'
            ]);

            // 🔍 EVENTO
            $fair = Fair::where('slug', $validated['slug'])->firstOrFail();

            // 🔍 PARTICIPANTE
            $participant = CyberwowParticipant::where('event_id', $fair->id)
                ->where('documentnumber', $validated['dni'])
                ->where('ruc', $validated['ruc'])
                ->first();

            if (!$participant) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Participante no encontrado'
                ]);
            }

            // ✅ GUARDAR DISCLAIMER
            $participant->disclaimer = 1;
            $participant->save();

            // =========================================
            // 📄 GENERAR PDF CON LA DATA DEL FORM
            // =========================================
            $pdf = Pdf::loadView('pdf.disclaimer', [
                'data' => $validated
            ])->output();

            // =========================================
            // 📧 ENVIAR CORREO + COPIA
            // =========================================
            Mail::mailer($mailer)
                ->to($validated['correo']) // usuario
                ->cc('feriascomerciales.pnte@produce.gob.pe') // copia
                ->send(new DisclaimerMail($pdf, $validated));

            return response()->json([
                'status' => 200,
                'message' => 'PDF generado y enviado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'Error en el servidor',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
