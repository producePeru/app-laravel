<?php

namespace App\Http\Controllers\Download;

use App\Exports\ParticipantDiagnosticExport;
use App\Http\Controllers\Controller;
use App\Models\MPDiagnostico;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\MPParticipant;
use Illuminate\Http\Request;

class DownloadExportDiagnosticMP extends Controller
{
    public function exportParticipantsExcel(Request $request)
    {
        $filters = [
            'name' => trim($request->input('name')),
        ];

        // =========================
        // PREGUNTAS (igual que index)
        // =========================
        $questions = MPDiagnostico::with('options')
            ->where('status', 1)
            ->where('type', '!=', 'l')
            ->orderBy('position', 'ASC')
            ->get();

        // =========================
        // QUERY BASE (igual)
        // =========================
        $query = MPParticipant::with([
            'diagnosticoResponses.option',
            'comercialActivity:id,name',
            'rubro:id,name',
            'economicSector:id,name',
            'typeDocument'
        ])
            ->withCount([
                'attendances as shares' => function ($q) {
                    $q->where('attendance', 1);
                }
            ]);

        // =========================
        // 🔍 MISMO FILTRO (CLONADO)
        // =========================
        if (!empty($filters['name'])) {
            $search = $filters['name'];

            $query->where(function ($q) use ($search) {
                $q->where('ruc', 'like', "%{$search}%")
                    ->orWhere('doc_number', 'like', "%{$search}%")
                    ->orWhereRaw(
                        "CONCAT(names, ' ', last_name, ' ', middle_name) LIKE ?",
                        ["%{$search}%"]
                    );
            });
        }

        // =========================
        // 🔥 MISMO ORDEN
        // =========================
        $query->orderByRaw('
        EXISTS (
            SELECT 1 
            FROM mp_diag_respuestas r 
            WHERE r.participant_id = mp_participantes.id
        ) DESC
    ');

        $query->orderBy('id', 'DESC');

        // =========================
        // 🚀 SIN PAGINACIÓN (CLAVE)
        // =========================
        $participants = $query->get();

        return Excel::download(
            new ParticipantDiagnosticExport($participants, $questions),
            'participantes_diagnostico.xlsx'
        );
    }
}
