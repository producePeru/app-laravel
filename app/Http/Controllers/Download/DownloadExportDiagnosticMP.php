<?php

namespace App\Http\Controllers\Download;

use App\Exports\ParticipantDiagnosticExport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\MPParticipant;
use Illuminate\Http\Request;

class DownloadExportDiagnosticMP extends Controller
{
    public function exportParticipantsExcel(Request $request)
    {
        $search = trim($request->input('name'));
        $startDate  = $request->input('startDate');
        $endDate    = $request->input('endDate');

        $participants = MPParticipant::with([
            'diagnosticoResponses.option',
            'comercialActivity',
            'rubro',
            'economicSector',
            'typeDocument'
        ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('ruc', 'like', "%{$search}%")
                        ->orWhere('doc_number', 'like', "%{$search}%")
                        ->orWhere('names', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                $query->whereHas('diagnosticoResponses', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('created_at', [
                        $startDate . ' 00:00:00',
                        $endDate . ' 23:59:59'
                    ]);
                });
            })
            ->get();

        return Excel::download(
            new ParticipantDiagnosticExport($participants),
            'participantes_diagnostico.xlsx'
        );
    }
}
