<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use App\Models\Advisory;


use App\Exports\AsesoriasExport;
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



}
