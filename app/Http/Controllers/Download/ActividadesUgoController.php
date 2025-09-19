<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use App\Exports\ActividadesExport;
use Maatwebsite\Excel\Facades\Excel;

class ActividadesUgoController extends Controller
{
    public function allActivitiesTheYear($year = 2025)
    {
        return Excel::download(new ActividadesExport($year), "actividades_{$year}.xlsx");
    }
}
