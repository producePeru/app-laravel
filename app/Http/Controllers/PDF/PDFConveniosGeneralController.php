<?php

namespace App\Http\Controllers\PDF;

use App\Http\Controllers\Controller;
use App\Models\Agreement;
use Illuminate\Http\Request;
use PDF;
use Carbon\Carbon;

Carbon::setLocale('es');

class PDFConveniosGeneralController extends Controller
{
    public function pdfConvenio($id)
    {

        $agreement = Agreement::with([
            'profile:id,user_id,name,lastname,middlename',
            'region',
            'provincia',
            'distrito',
            'archivosConvenios',
            'compromisos.profile:id,user_id,name,lastname,middlename',
            'compromisos.acciones',
            'compromisos.acciones.profile:id,user_id,name,lastname,middlename'

        ])->findOrFail($id);

        $data = [
            'region' => $agreement->region->name,
            'provincia' => $agreement->provincia->name,
            'distrito' => $agreement->distrito->name,
            'inicioConvenio' => Carbon::parse($agreement->startDate)->format('d-m-Y'),
            'finConvenio' => Carbon::parse($agreement->endDate)->format('d-m-Y'),
            'renovacion' => $agreement->renovation,
            'puntoFocal' => $agreement->focal,
            'puntoFocalCargo' => $agreement->focalCargo,
            'puntoFocalTelf' => $agreement->focalPhone,
            'aliado' => $agreement->aliado,
            'aliadoPhone' => $agreement->aliadoPhone,
            'detalles' => $agreement->observations,

            'date' => date('m/d/Y')
        ];



        $pdf = PDF::loadView('pdf.convenios', $data);

        // Descargar el PDF
        return $pdf->download('ejemplo.pdf');
    }
}
