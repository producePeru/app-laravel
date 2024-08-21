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

        $query = ActionPlans::with([
            'user.profile:id,user_id,name,lastname,middlename,notary_id,cde_id',
            'cde',
            'businessman',
            'businessman.city:id,name',
            'businessman.province:id,name',
            'businessman.district:id,name',
            'businessman.gender:id,avr',
            'component1'
        ]);

        if (in_array(2, $role_array) || in_array(7, $role_array)) {
            $query->where('asesor_id', $user_role['user_id']);
        }

        $query->latest();

        $data = $query->get();

        $result = $data->map(function ($item, $index) {
            return [
                'index' => $index + 1,
                'centro_empresa' => $item->cde->name,
                'asesor' => $item->user->profile->name.' '.$item->user->profile->lastname.' '.$item->user->profile->middlename,
                'emprendedor_region' => $item->businessman->city->name,
                'emprendedor_provincia' => $item->businessman->province->name,
                'emprendedor_distrito' => $item->businessman->district->name,
                'emprendedor_nombres' => $item->businessman->name.' '.$item->businessman->lastname.' '.$item->businessman->middlename,
                'ruc' => optional($item)->ruc ?? 'En trámite',
                'genero' => $item->businessman->gender->avr,
                'discapacidad' => $item->businessman->sick,
                'component_1' => optional($item->component1)->name ?? '-',
                'component_2' => optional($item->component2)->name ?? '-',
                'component_3' => optional($item->component3)->name ?? '-',
                'numberSessions' => $item->numberSessions,
                'startDate' => $item->startDate,
                'endDate' => $item->endDate,
                'totalDate' => $item->totalDate,
                'actaCompromiso' => $item->actaCompromiso,
                'envioCorreo' => $item->envioCorreo,
                'updated_at' => Carbon::parse($item->created_at)->format('d-m-Y'),
            ];
        });

        // return $result;

        return Excel::download(new ActionPlansExport($result), 'action-plans.xlsx');
    }
}

// php artisan make:export ActionPlansExport

