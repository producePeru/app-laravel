<?php

namespace App\Http\Controllers\Download;

use App\Exports\CpRegistrosExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DownloadComprasPeruController extends Controller
{
    public function exportExcel()
    {
        return Excel::download(
            new CpRegistrosExport,
            'cp_registros.xlsx'
        );
    }
}
