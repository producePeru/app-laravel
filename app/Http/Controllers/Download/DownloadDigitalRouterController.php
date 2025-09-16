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
        // $user_role = getUserRole();
        // $role_array = $user_role['role_id'];

        // return $role_array;

        $data = collect($request->all());


        // if (in_array(1, $role_array) || in_array(5, $role_array)) {
        //     // Roles 1 y 5 pueden ver todos los registros, no aplicamos filtro adicional
        // } elseif (in_array(2, $role_array) || in_array(7, $role_array)) {

        //     $data = $data->where('user_id', $user_role['user_id']);

        // } else {
        //     return response()->json(['error' => 'Unauthorized :C', 'status' => 409]);
        // }


        $result = $data->map(function ($item, $index) {

            return [
                'index' => $index + 1,

                'date' => $item['date'] ?? '',
                'asesor_documentnumber' => $item['asesor_documentnumber'] ?? '',
                'asesor_name' => $item['asesor_name'] ?? '',
                'asesor_cde' => $item['asesor_cde'] ?? '',

                'supervisador' => $item['supervisador'] ?? '',

                'documentnumber' => $item['documentnumber'] ?? '',
                'typedocument' => $item['typedocument'] ?? '',
                'country' => $item['country'] ?? '',
                'birthdate' => $item['birthdate'] ?? '',
                'lastname' => $item['lastname'] ?? '',
                'name' => $item['name'] ?? '',
                'gender' => $item['gender'] ?? '',
                'sick' => $item['sick'] ?? '',
                'phone' => $item['phone'] ?? '',
                'email' => $item['email'] ?? '',

                'ruc' => $item['ruc'] ?? '',
                'comercialName' => $item['comercialName'] ?? '',
                'socialReason' => $item['socialReason'] ?? '',

                'region' => $item['region'] ?? '',
                'province' => $item['province'] ?? '',
                'district' => $item['district'] ?? '',
                'address' => $item['address'] ?? '',
                'comercialactivity' => $item['comercialactivity'] ?? '',
                'economicsector' => $item['economicsector'] ?? '',

                'status' => $item['status'] == 1 ? 'SI' : 'NO'
            ];
        });

        return Excel::download(new DigitalRoutesExport($result), 'ruta-digital.xlsx');
    }
}
