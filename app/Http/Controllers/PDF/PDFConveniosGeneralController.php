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
            'profile:id,user_id,name,lastname,middlename',
            'region',
            'provincia',
            'distrito',
            'archivosConvenios',
            'compromisos.profile:id,user_id,name,lastname,middlename',
            'compromisos.acciones' => function ($query) use ($startDate, $endDate) {
                // Apply date filtering if both dates are provided
                if ($startDate && $endDate) {
                    $query->whereBetween('date', [$startDate, $endDate]);
                }
            },
            'compromisos.acciones.profile:id,user_id,name,lastname,middlename'
        ])->findOrFail($id);

        // Prepare data for the PDF
        $data = [
            'entity' => $agreement->alliedEntity,
            'region' => $agreement->region->name,
            'provincia' => $agreement->provincia->name,
            'distrito' => $agreement->distrito->name,
            'ruc' => $agreement->ruc,
            'componente' => $agreement->components,
            'inicioConvenio' => Carbon::parse($agreement->startDate)->format('d-m-Y'),
            'finConvenio' => Carbon::parse($agreement->endDate)->format('d-m-Y'),
            'renovacion' => $agreement->renovation,
            'puntoFocal' => $agreement->focal,
            'puntoFocalCargo' => $agreement->focalCargo,
            'puntoFocalTelf' => $agreement->focalPhone,
            'aliado' => $agreement->aliado,
            'aliadoPhone' => $agreement->aliadoPhone,
            'detalles' => $agreement->observations,
            'compromisos' => $agreement->compromisos,
            'date' => date('m/d/Y')
        ];

        // Load the PDF view with the data
        $pdf = PDF::loadView('pdf.convenios', $data);

        // Download the PDF
        return $pdf->download('ejemplo.pdf');
    }

}
