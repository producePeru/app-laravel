<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\ActionPlans;

use App\Exports\ActionPlansExport;
use Carbon\Carbon;

Carbon::setLocale('es');

class DownloadActionsPlanController extends Controller
{
    public function exportActionPlans(Request $request)
    {
        $user_role = getUserRole();
        $role_array = $user_role['role_id'];

        $data = collect($request->all());


        if (in_array(2, $role_array) || in_array(7, $role_array)) {
            $data = $data->where('asesor_dni', $user_role['user_id']);
        }

        $result = $data->map(function ($item, $index) {
            return [
                'index' => $index + 1,
                'centro_empresa' => $item['centro_empresa'] ?? '',
                'asesor' => $item['asesor'] ?? '',
                'emprendedor_region' => $item['emprendedor_region'] ?? '',
                'emprendedor_provincia' => $item['emprendedor_provincia'] ?? '',
                'emprendedor_distrito' => $item['emprendedor_distrito'] ?? '',
                'emprendedor_nombres' => $item['emprendedor_nombres'] ?? '',
                // 'emprendedor_dni' => $item['emprendedor_dni'] ?? '',
                'ruc' => $item['ruc'] ?? 'En trámite',
                'genero' => $item['genero'] ?? '',
                'discapacidad' => $item['discapacidad'] ?? '',
                'component_1' => $item['component_1'] ?? '-',
                'component_2' => $item['component_2'] ?? '-',
                'component_3' => $item['component_3'] ?? '-',
                'numberSessions' => $item['numberSessions'] ?? 0,
                'startDate' => isset($item['startDate']) ? Carbon::parse($item['startDate'])->format('d/m/Y') : '',
                'endDate' => isset($item['endDate']) ? Carbon::parse($item['endDate'])->format('d/m/Y') : '',
                'totalDate' => $item['totalDate'] ?? 0,
                'actaCompromiso' => $item['actaCompromiso'] ?? null,
                'envioCorreo' => $item['envioCorreo'] ?? null,
                'updated_at' => isset($item['updated_at']) ? Carbon::parse($item['updated_at'])->format('d/m/Y') : ''
            ];
        });

        // return $result;

        return Excel::download(new ActionPlansExport($result), 'action-plans.xlsx');
    }
}

// php artisan make:export ActionPlansExport
