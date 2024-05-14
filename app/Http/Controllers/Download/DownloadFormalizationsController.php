<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Formalization20;
use App\Models\Formalization10;
use App\Models\Advisory;
use Illuminate\Http\Request;

use App\Exports\AsesoriasExport;
use App\Exports\FormalizationRUC10Export;
use App\Exports\FormalizationRUC20Export;

use Carbon\Carbon;
Carbon::setLocale('es');


class DownloadFormalizationsController extends Controller
{

    // public function exportAsesories(Request $request)
    // {
    //     $filters = [
    //         'dateStart' => $request->input('dateStart'),
    //         'dateEnd' => $request->input('dateEnd'),
    //     ];
    //     $results = Advisory::ByUserId(13)->descargaExcelAsesorias($filters);
    //     return response()->json($results, 200);
    // return Excel::download(new AsesoriasExport, 'asesorias-pnte.xlsx'); before*
    // }
    public function exportAsesories(Request $request)
    {
        $dateStart = $request->input('dateStart');
        $dateEnd = $request->input('dateEnd');

        $export = new AsesoriasExport();
        $export->dateStart = $dateStart;
        $export->dateEnd = $dateEnd;

        return Excel::download($export, 'asesorias-pnte.xlsx');



        // $query = Advisory::descargaExcelAsesorias([
        //     'dateStart' => $request->dateStart,
        //     'dateEnd' => $request->dateEnd,
        // ]);

        // return $query;
    }


    public function exportFormalizationsRuc10(Request $request)
    {
        $dateStart = $request->input('dateStart');
        $dateEnd = $request->input('dateEnd');

        $export = new FormalizationRUC10Export();
        $export->dateStart = $dateStart;
        $export->dateEnd = $dateEnd;

        return Excel::download($export, 'formalizaciones_ruc10_pnte.xlsx');
        // return Excel::download(new FormalizationRUC10Export, 'formalizaciones_ruc10_pnte.xlsx');
    }



    public function exportFormalizationsRuc20(Request $request)
    {
        $dateStart = $request->input('dateStart');
        $dateEnd = $request->input('dateEnd');

        $export = new FormalizationRUC20Export();
        $export->dateStart = $dateStart;
        $export->dateEnd = $dateEnd;

        return Excel::download($export, 'formalizaciones_ruc20_pnte.xlsx');
        // return Excel::download(new FormalizationRUC20Export, 'formalizaciones_ruc20_pnte.xlsx');
        // $results = Formalization20::allFormalizations20();
        // return $results;
    }
}
