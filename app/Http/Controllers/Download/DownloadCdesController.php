<?php

namespace App\Http\Controllers\Download;

use App\Exports\CdeExport;
use App\Http\Controllers\Controller;
use App\Models\Cde;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DownloadCdesController extends Controller
{
    public function exportCdes(Request $request)
    {
        try {
            $filters = $request->query();

            $userRole = getUserRole();
            $roleIds  = $userRole['role_id'];
            $userId   = $userRole['user_id'];

            $query = Cde::with('cdetype')->orderBy('created_at', 'desc');;

            // $query->withCdesAndRelations($filters);

            ini_set('memory_limit', '2G');
            set_time_limit(300);

            $cdes = [];
            $globalIndex = 1;

            $query->chunk(1000, function ($rows) use (&$cdes, &$globalIndex) {
                foreach ($rows as $cde) {
                    $cdes[] = [
                        'index'     => $globalIndex++,
                        'name'      => strtoupper($cde->name) ?? null,
                        'city'      => strtoupper($cde->city) ?? null,
                        'province'  => strtoupper($cde->province) ?? null,
                        'district'  => strtoupper($cde->district) ?? null,
                        'address'   => strtoupper($cde->address) ?? null,
                        'cdetype'   => $cde->cdetype ? $cde->cdetype->name : "-",
                    ];
                }
            });

            return Excel::download(new CdeExport($cdes), 'cdes.xlsx');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrió un error al generar el reporte.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
