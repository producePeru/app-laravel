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

    public function exportAsesories(Request $request)
    {
        $user_role = getUserRole();
        $role_array = $user_role['role_id'];
        $dateStart = $request->input('dateStart');
        $dateEnd = $request->input('dateEnd');

        // Inicializar la consulta
        $advisories = Advisory::with([
            'economicsector:id,name',
            'comercialactivity:id,name',
            'user.profile:id,user_id,name,lastname,middlename,notary_id,cde_id',
            'user.profile.notary:id,name',
            'user.profile.cde',
            'people:id,name,lastname,middlename,country,birthday,hasSoon,sick,phone,email,documentnumber,typedocument_id,gender_id,country_id',
            'people.typedocument:id,id,name,avr',
            'people.gender:id,id,name,avr',
            'component:id,name',
            'theme:id,name',
            'modality:id,name',

            'people.pais:id,name',

            'city:id,name',
            'province:id,name',
            'district:id,name',
            'cde:id,name,city,province,district',
            'supervisor.supervisorUser.profile:id,user_id,name,lastname,middlename'
        ]);

        // Filtrar por fechas si se proporcionan
        if ($dateStart && $dateEnd) {
            try {
                $dateStart = Carbon::createFromFormat('Y-m-d', $dateStart)->startOfDay();
                $dateEnd = Carbon::createFromFormat('Y-m-d', $dateEnd)->endOfDay();
                $advisories = $advisories->whereBetween('created_at', [$dateStart, $dateEnd]);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Invalid date format'], 400);
            }
        }

        // Filtrar por roles
        if (in_array(2, $role_array) || in_array(7, $role_array)) {
            $advisories = $advisories->where('user_id', auth()->id());
        } elseif (in_array(1, $role_array) || in_array(5, $role_array)) {
            // No additional filtering needed, so the query remains as is.
        } else {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Obtener los registros y ordenarlos
        $advisories = $advisories->get()->sortByDesc('created_at');

        // Mapping
        $index = 0;
        $result = $advisories->map(function ($advisory) use (&$index) {
            $index++;

            $asesor = $advisory->user->profile->notary_id
                ? "NOTARÍA: " . strtoupper($advisory->user->profile->notary->name) . ' - ' . strtoupper($advisory->user->profile->name . ' ' . $advisory->user->profile->lastname . ' ' . $advisory->user->profile->middlename)
                : strtoupper($advisory->user->profile->name . ' ' . $advisory->user->profile->lastname . ' ' . $advisory->user->profile->middlename);

            return [
                'index' => $index,
                'fecha_registro' => Carbon::parse($advisory->created_at)->format('d/m/Y'),
                'asesor' => $asesor,
                'region_cde' => optional($advisory->cde)->city ? optional($advisory->cde)->city : $advisory->user->profile->cde->city,
                'provincia_cde' => optional($advisory->cde)->province ? optional($advisory->cde)->province : $advisory->user->profile->cde->province,
                'distrito_cde' => optional($advisory->cde)->district ? optional($advisory->cde)->district : $advisory->user->profile->cde->district,
                'tipo_documento' => $advisory->people->typedocument->avr,
                'numero_documento' => $advisory->people->documentnumber,

                'nombre_pais' => $advisory->people->pais ? strtoupper($advisory->people->pais->name) : ($advisory->people->country ? strtoupper($advisory->people->country) : 'PERU'),

                'fecha_nacimiento' => Carbon::parse($advisory->people->birthday)->format('d/m/Y'),
                'apellido_paterno' => strtoupper($advisory->people->lastname),
                'apellido_materno' => strtoupper($advisory->people->middlename),
                'nombre' => strtoupper($advisory->people->name),
                'genero' => $advisory->people->gender->avr,
                'discapacidad' => strtoupper($advisory->people->sick),
                'hijos' => $advisory->people->hasSoon,
                'telefono' => $advisory->people->phone,
                'correo' => $advisory->people->email,
                'supervisador' => strtoupper(
                    optional(optional(optional($advisory->supervisor)->supervisorUser)->profile)->name . ' ' .
                    optional(optional(optional($advisory->supervisor)->supervisorUser)->profile)->lastname . ' ' .
                    optional(optional(optional($advisory->supervisor)->supervisorUser)->profile)->middlename
                ),
                'region_negocio' => $advisory->city->name,
                'provincia_negocio' => $advisory->province->name,
                'distrito_negocio' => $advisory->district->name,
                'ruc' => $advisory->ruc,
                'sector_economico' => $advisory->economicsector->name,
                'actividad_comercial' => $advisory->comercialactivity->name,
                'componente' => $advisory->component->name,
                'tema' => $advisory->theme->name,
                'descripcion' => $advisory->observations,
                'modalidad' => $advisory->modality->name
            ];

        })->values(); // Convierte la colección en un array indexado
        return Excel::download(new AsesoriasExport($result), 'asesorias-pnte.xlsx');
    }


    public function exportFormalizationsRuc10(Request $request)
    {
        $user_role = getUserRole();
        $role_array = $user_role['role_id'];
        $dateStart = $request->input('dateStart');
        $dateEnd = $request->input('dateEnd');

        $data = Formalization10::with([
            'economicsector:id,name',
            'comercialactivity:id,name',
            'detailprocedure:id,name',

            'user.profile:id,user_id,name,lastname,middlename,notary_id,cde_id',
            'user.profile.notary:id,name',
            'user.profile.cde',
            'people:id,name,lastname,middlename,country,birthday,hasSoon,sick,phone,email,documentnumber,typedocument_id,gender_id,country_id',
            'people.typedocument:id,id,name,avr',
            'people.gender:id,id,name,avr',
            'modality:id,name',
            'people.pais:id,name',
            'city:id,name',
            'province:id,name',
            'district:id,name',
            'cde:id,name,city,province,district',
            'supervisor.supervisorUser.profile:id,user_id,name,lastname,middlename'
        ]);

        // Filtrar por fechas
        if ($dateStart && $dateEnd) {
            try {
                $dateStart = Carbon::createFromFormat('Y-m-d', $dateStart)->startOfDay();
                $dateEnd = Carbon::createFromFormat('Y-m-d', $dateEnd)->endOfDay();
                $data = $data->whereBetween('created_at', [$dateStart, $dateEnd]);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Invalid date format'], 400);
            }
        }

        // Filtrar por roles
        if (in_array(2, $role_array) || in_array(7, $role_array)) {
            $data = $data->where('user_id', auth()->id());
        } elseif (in_array(1, $role_array) || in_array(5, $role_array)) {
            // solo para los super admins
        } else {
            return response()->json(['error' => 'Unauthorized'], 403);
        }


        $data = $data->get()->sortByDesc('created_at');

        // Mapping
        $index = 0;
        $result = $data->map(function ($advisory) use (&$index) {

            $index++;

            $asesor = $advisory->user->profile->notary_id
            ? "NOTARÍA:" .' '. strtoupper($advisory->user->profile->notary->name) . ' - ' . strtoupper($advisory->user->profile->name . ' ' . $advisory->user->profile->lastname . ' ' . $advisory->user->profile->middlename)
            : strtoupper($advisory->user->profile->name . ' ' . $advisory->user->profile->lastname . ' ' . $advisory->user->profile->middlename);


            return [
                'index' => $index,
                'fecha_registro' => Carbon::parse($advisory->created_at)->format('d/m/Y'),
                'asesor'         => $asesor,
                'region_cde' => optional($advisory->cde)->city ? optional($advisory->cde)->city : $advisory->user->profile->cde->city,
                'provincia_cde' => optional($advisory->cde)->province ? optional($advisory->cde)->province : $advisory->user->profile->cde->province,
                'distrito_cde' => optional($advisory->cde)->district ? optional($advisory->cde)->district : $advisory->user->profile->cde->district,
                'tipo_documento' => $advisory->people->typedocument->avr,
                'numero_documento' => $advisory->people->documentnumber,
                'nombre_pais' => $advisory->people->pais ? strtoupper($advisory->people->pais->name) : ($advisory->people->country ? strtoupper($advisory->people->country) : 'PERU'),
                'fecha_nacimiento' => Carbon::parse($advisory->people->birthday)->format('d/m/Y'),
                'apellido_paterno' => strtoupper($advisory->people->lastname),
                'apellido_materno' => strtoupper($advisory->people->middlename),
                'nombre' => strtoupper($advisory->people->name),
                'genero' => $advisory->people->gender->avr,
                'discapacidad' => strtoupper($advisory->people->sick),
                'hijos' => $advisory->people->hasSoon,
                'telefono' => $advisory->people->phone,
                'correo' => $advisory->people->email,
                'tipo_servicio' => 'PPNN (RUC 10)',
                'supervisador' =>  strtoupper(
                optional(optional(optional($advisory->supervisor)->supervisorUser)->profile)->name .' '.
                optional(optional(optional($advisory->supervisor)->supervisorUser)->profile)->lastname .' '.
                optional(optional(optional($advisory->supervisor)->supervisorUser)->profile)->middlename),
                'region_negocio' => $advisory->city->name,
                'provincia_negocio' => $advisory->province->name,
                'distrito_negocio' => $advisory->district->name,
                'direccion' => $advisory->address,
                'ruc' => $advisory->ruc,
                'sector_economico' => $advisory->economicsector->name,
                'actividad_comercial' => $advisory->comercialactivity->name,
                'detalle_tramite' => $advisory->detailprocedure->name,
                'modalidad' => $advisory->modality->name
            ];

        })->values(); // Convierte la colección en un array indexado
        // return $result;
        return Excel::download(new FormalizationRUC10Export($result), 'asesorias-pnte.xlsx');
    }



    public function exportFormalizationsRuc20(Request $request)
    {
        $user_role = getUserRole();
        $role_array = $user_role['role_id'];
        $dateStart = $request->input('dateStart');
        $dateEnd = $request->input('dateEnd');

        $data = Formalization20::with([
            'regime:id,name',
            'notary:id,name',
            'typecapital:id,name',

            'user.profile:id,user_id,name,lastname,middlename,notary_id,cde_id',
            'user.profile.notary:id,name',
            'user.profile.cde',
            'people:id,name,lastname,middlename,country,birthday,hasSoon,sick,phone,email,documentnumber,typedocument_id,gender_id,country_id',
            'people.typedocument:id,id,name,avr',
            'people.gender:id,id,name,avr',
            'modality:id,name',
            'people.pais:id,name',
            'city:id,name',
            'province:id,name',
            'district:id,name',
            'cde:id,name,city,province,district',
            'supervisor.supervisorUser.profile:id,user_id,name,lastname,middlename'
        ]);

        // Filtrar por fechas
        if ($dateStart && $dateEnd) {
            try {
                $dateStart = Carbon::createFromFormat('Y-m-d', $dateStart)->startOfDay();
                $dateEnd = Carbon::createFromFormat('Y-m-d', $dateEnd)->endOfDay();
                $data = $data->whereBetween('created_at', [$dateStart, $dateEnd]);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Invalid date format'], 400);
            }
        }

        // Filtrar por roles
        if (in_array(2, $role_array) || in_array(7, $role_array)) {
            $data = $data->where('user_id', auth()->id());
        } elseif (in_array(1, $role_array) || in_array(5, $role_array)) {
            // solo para los super admins
        } else {
            return response()->json(['error' => 'Unauthorized'], 403);
        }


        $data = $data->get()->sortByDesc('created_at');

        // Mapping
        $index = 0;
        $result = $data->map(function ($advisory) use (&$index) {

            $index++;

            $asesor = $advisory->user->profile->notary_id
            ? "NOTARÍA:" .' '. strtoupper($advisory->user->profile->notary->name) . ' - ' . strtoupper($advisory->user->profile->name . ' ' . $advisory->user->profile->lastname . ' ' . $advisory->user->profile->middlename)
            : strtoupper($advisory->user->profile->name . ' ' . $advisory->user->profile->lastname . ' ' . $advisory->user->profile->middlename);


            return [
                'index' => $index,
                'fecha_registro' => Carbon::parse($advisory->created_at)->format('d/m/Y'),
                'asesor'         => $asesor,
                'region_cde' => optional($advisory->cde)->city ? optional($advisory->cde)->city : $advisory->user->profile->cde->city,
                'provincia_cde' => optional($advisory->cde)->province ? optional($advisory->cde)->province : $advisory->user->profile->cde->province,
                'distrito_cde' => optional($advisory->cde)->district ? optional($advisory->cde)->district : $advisory->user->profile->cde->district,
                'tipo_documento' => $advisory->people->typedocument->avr,
                'numero_documento' => $advisory->people->documentnumber,
                'nombre_pais' => $advisory->people->pais ? strtoupper($advisory->people->pais->name) : ($advisory->people->country ? strtoupper($advisory->people->country) : 'PERU'),
                'fecha_nacimiento' => Carbon::parse($advisory->people->birthday)->format('d/m/Y'),
                'apellido_paterno' => strtoupper($advisory->people->lastname),
                'apellido_materno' => strtoupper($advisory->people->middlename),
                'nombre' => strtoupper($advisory->people->name),
                'genero' => $advisory->people->gender->avr,
                'discapacidad' => strtoupper($advisory->people->sick),
                'hijos' => $advisory->people->hasSoon,
                'telefono' => $advisory->people->phone,
                'correo' => $advisory->people->email,
                'tipo_servicio' => 'PPNN (RUC 20)',
                'supervisador' =>  strtoupper(
                optional(optional(optional($advisory->supervisor)->supervisorUser)->profile)->name .' '.
                optional(optional(optional($advisory->supervisor)->supervisorUser)->profile)->lastname .' '.
                optional(optional(optional($advisory->supervisor)->supervisorUser)->profile)->middlename),
                'region_negocio' => $advisory->city->name,
                'provincia_negocio' => $advisory->province->name,
                'distrito_negocio' => $advisory->district->name,
                'direccion' => $advisory->address,
                'ruc' => $advisory->ruc,
                'sector_economico' => $advisory->economicsector->name,
                'actividad_comercial' => $advisory->comercialactivity->name,
                'fecha_recepcion' => Carbon::parse($advisory->dateReception)->format('d/m/Y'),
                'fecha_tramite' => Carbon::parse($advisory->dateTramite)->format('d/m/Y'),
                'nombre_empresas' => $advisory->nameMype,
                'regimen' => $advisory->regime->name,
                'bic' => $advisory->isbic,
                'numero_solicitud' => $advisory->numbernotary,
                'notaria' => $advisory->notary->name,
                'tipo_capital' => optional($advisory->typecapital)->name,
                'monto_capital' => $advisory->montocapital,
                'modalidad' => $advisory->modality->name
            ];

        })->values(); // Convierte la colección en un array indexado
        // return $result;
        return Excel::download(new FormalizationRUC20Export($result), 'asesorias-pnte.xlsx');
    }
}
