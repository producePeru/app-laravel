<?php

namespace App\Http\Controllers\Download;

use App\Exports\DigitalRoutesExport;
use App\Http\Controllers\Controller;
use App\Models\Digitalroute;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DownloadDigitalRouterController extends Controller
{

    public function exportDigitalRouter(Request $request)
    {
        $user_role = getUserRole();
        $role_array = $user_role['role_id'];

        $query = Digitalroute::with([
            'profile',
            'profile.cde:id,name',
            'profile.supervisor',

            'user.misupervisor.supervisor.profile',

            'person',
            'person.typedocument:id,name',
            'person.pais:id,name',
            'person.gender:id,avr',
            'mype',
            'mype.region:id,name',
            'mype.province:id,name',
            'mype.district:id,name',
            'mype.comercialactivity:id,name',
            'mype.economicsector:id,name'
        ]);


        // Filtrado según roles
        if (in_array(1, $role_array) || in_array(5, $role_array)) {
            // Roles 1 y 5 pueden ver todos los registros, no aplicamos filtro adicional
        } elseif (in_array(2, $role_array) || in_array(7, $role_array)) {
            $user_id = $user_role['user_id'];
            $query->where('user_id', $user_id);
        } else {
            return response()->json(['error' => 'Unauthorized', 'status' => 409]);
        }


        // Obtener fechas de los parámetros de la URL
        $start = $request->query('start');
        $end = $request->query('end');

        // Aplicar filtro por fechas si los parámetros están presentes
        if ($start && $end) {
            $query->whereBetween('created_at', [
                Carbon::parse($start)->startOfDay(),
                Carbon::parse($end)->endOfDay()
            ]);
        }


        $query->latest();

        $data = $query->get();

        $result = $data->map(function ($item, $index) {

            $supervisador = $item->user->misupervisor?->supervisor->profile
            ? $item->user->misupervisor->supervisor->profile->name . ' ' . $item->user->misupervisor->supervisor->profile->lastname . ' ' . $item->user->misupervisor->supervisor->profile->middlename
            : 'Sin supervisor';

            return [
                'index' => $index + 1,

                'date' => Carbon::parse($item->created_at)->format('d/m/Y H:i'),
                'asesor_documentnumber' => $item->profile->documentnumber,
                'asesor_name' => $item->profile->name . ' ' . $item->profile->lastname . ' ' . $item->profile->middlename,
                'asesor_cde' => $item->profile->cde->name,
                'supervisador' => $supervisador,

                'documentnumber' => $item->person->documentnumber,
                'typedocument' => $item->person->typedocument->name,
                'country' => $item->person->pais->name,
                'birthdate' => Carbon::parse($item->person->birthday)->format('d/m/Y'),
                'lastname' => $item->person->lastname . ' ' . $item->person->middlename,
                'name' => $item->person->name,
                'gender' => $item->person->gender->avr,
                'sick' => $item->person->sick == 'yes' ? 'SI' : 'NO',
                'phone' => $item->person->phone,
                'email' => $item->person->email,

                'ruc' => $item->mype->ruc,
                'region' => $item->mype->region->name,
                'province' => $item->mype->province->name,
                'district' => $item->mype->district->name,
                'address' => $item->mype->address,
                'comercialactivity' => $item->mype->comercialactivity->name,
                'economicsector' => $item->mype->economicsector->name,

                'status' => $item->status == 1 ? 'SI' : 'NO'
            ];
        });

        return Excel::download(new DigitalRoutesExport($result), 'ruta-digital.xlsx');

    }


}
