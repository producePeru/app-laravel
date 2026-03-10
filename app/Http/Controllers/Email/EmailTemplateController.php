<?php

namespace App\Http\Controllers\Email;

use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Jobs\SendHonstigerTemplateEmailJob;
use App\Models\EmailSend;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EmailTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $today = Carbon::today()->toDateString();

        // 🔥 Total de envíos del día
        $totalToday = EmailSend::where('date', $today)->sum('count');

        $templates = EmailTemplate::select('id', 'name', 'content')->get();

        return response()->json([
            'status' => 200,
            'message' => 'Plantillas cargadas correctamente',
            'total_envios_hoy' => $totalToday,
            'data' => $templates
        ], 200);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $template = EmailTemplate::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Plantilla creada correctamente',
            'data' => $template,
            'status' => 200
        ]);
    }

    /**
     * Para el select.
     */
    public function showSelect()
    {
        $templates = EmailTemplate::select('id', 'name', 'content')
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'name' => $t->name,
                    'content' => $t->content,
                    'label' => $t->name,  // Para el select
                    'value' => $t->id     // Para el select
                ];
            });

        return response()->json([
            'status' => 200,
            'message' => 'Plantillas cargadas correctamente',
            'data' => $templates
        ], 200);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function validateEmails(Request $request)
    {
        $emails = $request->all();

        if (!is_array($emails)) {
            return response()->json([
                'success' => false,
                'message' => 'Debe enviar un array de correos'
            ], 400);
        }

        $valid = [];
        $invalid = [];

        foreach ($emails as $email) {

            $validator = Validator::make(
                ['email' => $email],
                ['email' => 'required|email:rfc,dns']
            );

            if ($validator->fails()) {
                $invalid[] = $email;
            } else {
                $valid[] = $email;
            }
        }

        return response()->json([
            'success' => true,
            'valid_emails' => $valid,
            'invalid_emails' => $invalid,
            'total' => count($emails)
        ], 200);
    }

    public function sendEmails(Request $request)
    {
        $request->validate([
            'template_id' => 'required|exists:email_templates,id',
            'emails' => 'required|array|max:1000',
            // 'emails.*' => 'email:rfc,dns',
            'asunto' => 'required|string|max:255',
            'copia' => 'nullable|array',
            // 'copia.*' => 'email'
        ]);

        $template = EmailTemplate::findOrFail($request->template_id);
        $emails = $request->emails;
        $asunto = $request->asunto;
        $copias = $request->copia ?? [];

        $today = Carbon::today()->toDateString();

        $emailSend = EmailSend::firstOrCreate(
            [
                'date' => $today,
                'template_id' => $template->id
            ],
            [
                'count' => 0
            ]
        );

        $currentCount = $emailSend->count;
        $requestCount = count($emails);

        if ($currentCount >= 3000) {
            return response()->json([
                'success' => false,
                'message' => 'Se ha alcanzado el límite diario de 3000 envíos para este template.'
            ], 400);
        }

        if (($currentCount + $requestCount) > 3000) {
            $remaining = 3000 - $currentCount;

            return response()->json([
                'success' => false,
                'message' => "Solo puedes enviar {$remaining} correos más hoy con este template."
            ], 400);
        }

        // Bloquear cupo inmediatamente
        $emailSend->increment('count', $requestCount);

        foreach ($emails as $index => $email) {
            SendHonstigerTemplateEmailJob::dispatch(
                $email,
                $template,
                $asunto,
                $copias
            )->delay(now()->addSeconds($index * 30));
        }

        return response()->json([
            'success' => true,
            'message' => 'Correos encolados correctamente.',
            'total_queued' => $requestCount
        ], 200);
    }

    public function deleteTemplate($id)
    {
        $template = EmailTemplate::find($id);

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Template no encontrado'
            ], 404);
        }

        $template->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Template eliminado correctamente'
        ], 200);
    }

    public function updateTemplate(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:emails,id',
            'name' => 'required|string|max:255',
            'content' => 'required|string'
        ]);

        $template = EmailTemplate::find($request->id);

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Plantilla no encontrada'
            ], 404);
        }

        $template->update([
            'name' => $request->name,
            'content' => $request->content
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Plantilla actualizada correctamente',
            'status' => 200
        ], 200);
    }
}
