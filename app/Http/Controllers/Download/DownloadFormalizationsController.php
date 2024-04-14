<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Formalization20;
use App\Models\Formalization10;

use App\Exports\AsesoriasExport;
use App\Exports\FormalizationRUC10Export;
use App\Exports\FormalizationRUC20Export;

use Carbon\Carbon;
Carbon::setLocale('es');


class DownloadFormalizationsController extends Controller
{
    public function exportAsesories()
    {
        return Excel::download(new AsesoriasExport, 'asesorias-pnte.xlsx');
        // $results = Advisory::allNotaries();
        // return $results;
    }

    public function exportFormalizationsRuc10()
    {
        // $results = Formalization10::allFormalizations10();
        // return $results;
        return Excel::download(new FormalizationRUC10Export, 'formalizaciones_ruc10_pnte.xlsx');
    }

    public function exportFormalizationsRuc20()
    {
        return Excel::download(new FormalizationRUC20Export, 'formalizaciones_ruc20_pnte.xlsx');
        // $results = Formalization20::allFormalizations20();
        // return $results;
    }
}
