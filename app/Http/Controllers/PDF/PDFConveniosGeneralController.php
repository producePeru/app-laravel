<?php

namespace App\Http\Controllers\PDF;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Agreement;
use PDF;
use Carbon\Carbon;

Carbon::setLocale('es');

class PDFConveniosGeneralController extends Controller
{
    public function pdfConvenio($id)
    {
        // Retrieve start and end dates from the query parameters
        $startDate = request()->query('start');
        $endDate = request()->query('end');

        // Fetch the agreement with related data and filter actions based on dates
        $agreement = Agreement::with([
            'user:id,name,lastname,middlename',
            'region',
            'provincia',
            'distrito',
            'archivosConvenios',
            'compromisos.user:id,name,lastname,middlename',
            'compromisos.acciones' => function ($query) use ($startDate, $endDate) {
                // Apply date filtering if both dates are provided
                if ($startDate && $endDate) {
                    $query->whereBetween('date', [$startDate, $endDate]);
                }
            },
            'compromisos.acciones.user:id,name,lastname,middlename'
        ])->findOrFail($id);

        // Prepare data for the PDF
        $fmtDate = fn ($value) => $value
            ? Carbon::parse($value)->format('d-m-Y')
            : '';

        // Semáforo del convenio (mismo criterio que el frontend)
        $estado = 'SIN FECHA';
        $estadoColor = '#8c8c8c';
        if ($agreement->endDate) {
            // end - today: positivo = días restantes (igual que dayjs en el frontend)
            $diffDays = (int) Carbon::today()->diffInDays(
                Carbon::parse($agreement->endDate)->startOfDay(),
                false
            );
            if ($diffDays < 0) {
                $estado = 'CULMINADO';
                $estadoColor = '#dc2626';
            } elseif ($diffDays <= 30) {
                $estado = 'PRÓXIMO A VENCER';
                $estadoColor = '#faad14';
            } else {
                $estado = 'VIGENTE';
                $estadoColor = '#16a34a';
            }
        }

        $data = [
            'entity' => $agreement->alliedEntity,
            'nombre' => $agreement->nombre,
            'fechaSuscripcion' => $fmtDate($agreement->fecha_suscripcion ?? $agreement->startDate),
            'finConvenio' => $fmtDate($agreement->endDate),
            'fechaAdenda' => $fmtDate($agreement->fecha_adenda),
            'estado' => $estado,
            'estadoColor' => $estadoColor,
            'periodoVigencia' => $agreement->observations,
            'titular' => $agreement->focal,
            'alternos' => $agreement->alternos ?? [],
            'titularAliado' => $agreement->aliado,
            'alternosAliados' => $agreement->alternos_aliados ?? [],
            'planTrabajo' => is_null($agreement->cuenta_plan_trabajo)
                ? '-'
                : ($agreement->cuenta_plan_trabajo ? 'SI' : 'NO'),
            'archivos' => $agreement->archivosConvenios,
            'compromisos' => $agreement->compromisos,
            'date' => date('m/d/Y'),
        ];

        // Load the PDF view with the data
        $pdf = PDF::loadView('pdf.convenios', $data);

        // Download the PDF
        return $pdf->download('ejemplo.pdf');
    }

}
