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
            'name'   => trim($request->input('name')),
            'status' => $request->input('status'), // 👈 agregado
        ];

        $questions = MPDiagnostico::with('options')
            ->where('status', 1)
            ->where('type', '!=', 'l')
            ->orderBy('position', 'ASC')
            ->get();

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

        if (!empty($filters['status'])) {

            if ($filters['status'] === 'DIAGNÓSTICO COMPLETADOS') {

                $query->whereHas('diagnosticoResponses');
            } elseif ($filters['status'] === 'DIAGNÓSTICO NO COMPLETADOS') {

                $query->whereDoesntHave('diagnosticoResponses');
            }
        }

        $query->orderByRaw('
        EXISTS (
            SELECT 1 
            FROM mp_diag_respuestas r 
            WHERE r.participant_id = mp_participantes.id
        ) DESC');

        $query->orderBy('id', 'DESC');

        // =========================
        // 🚀 SIN PAGINACIÓN
        // =========================
        $participants = $query->get();

        return Excel::download(
            new ParticipantDiagnosticExport($participants, $questions),
            'participantes_diagnostico.xlsx'
        );
    }
}
