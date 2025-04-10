<?php

namespace App\Http\Controllers\Download;

use App\Exports\NotaryExport;
use App\Http\Controllers\Controller;
use App\Models\Notary;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DownloadNotariesController extends Controller
{
    public function exportNotaries(Request $request)
    {

        try {
            $filters = $request->query();

            $userRole = getUserRole();
            $roleIds  = $userRole['role_id'];
            $userId   = $userRole['user_id'];

            $query = Notary::query();

            $query->withNotariesAndRelations($filters);

            ini_set('memory_limit', '2G');
            set_time_limit(300);

            $notaries = [];
            $globalIndex = 1;

            $query->chunk(1000, function ($rows) use (&$notaries, &$globalIndex) {
                foreach ($rows as $notary) {

                    $gastos = is_array($notary->gastos) ? $notary->gastos : json_decode($notary->gastos, true);
                    $gastos = $gastos ?? [];
                    $listaGastos = array_map('strip_tags', array_column($gastos, 'gasto'));
                    $listaCondiciones = array_map('strip_tags', array_column($gastos, 'condicion'));


                    $notaries[] = [
                        'index'                 => $globalIndex++,
                        'notaria'               => strtoupper($notary->name) ?? null,
                        'city'                  => $notary->city->name ?? null,
                        'province'              => $notary->province->name ?? null,
                        'district'              => $notary->district->name ?? null,
                        'address'               => strtoupper($notary->address) ?? null,
                        'gastos'                => "- " . implode("\n- ", $listaGastos),
                        'condiciones'           => "- " . implode("\n- ", $listaCondiciones),
                        'biometrico'            => strip_tags($notary->biometrico) ?? null,
                        'socio'                 => strip_tags($notary->sociointerveniente) ?? null,
                        'contacto'              => strip_tags($notary->infocontacto) ?? null
                    ];
                }
            });

            return Excel::download(new NotaryExport($notaries), 'notaries.xlsx');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'OcurriÓ un error al generar el reporte.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
