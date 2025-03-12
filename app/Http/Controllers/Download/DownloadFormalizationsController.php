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
        $filters = [
            'asesor'    => $request->input('asesor'),
            'name'      => $request->input('name'),
            'dateStart' => $request->input('dateStart'),
            'dateEnd'   => $request->input('dateEnd'),
            'year'      => $request->input('year'),
        ];

        $userRole = getUserRole();
        $roleIds  = $userRole['role_id'];
        $userId   = $userRole['user_id'];

        $query = Advisory::query();

        if (in_array(1, $roleIds) || $userId === 1) {
            $query->withAdvisoryRangeDate($filters);
        } elseif (in_array(2, $roleIds) || in_array(7, $roleIds)) {
            $query->ByUserId($userId)->withAdvisoryRangeDate($filters);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $index = 1;
        $advisories = collect();

        foreach ($query->cursor() as $advisory) {
            $advisories->push([
                'index'                 => $index++,
                'date'                  => $advisory->created_at->format('d/m/Y'),
                'asesor'                => isset($advisory->user->profile) ? strtoupper($advisory->user->profile->lastname . ' ' . $advisory->user->profile->middlename . ' ' . $advisory->user->profile->name) : null,
                'asesor_cde_city'       => $advisory->sede->city ?? null,
                'asesor_cde_province'   => $advisory->sede->province ?? null,
                'asesor_cde_district'   => $advisory->sede->district ?? null,

                'emp_document_type'     => $advisory->people->typedocument->avr ?? null,
                'emp_document_number'   => $advisory->people->documentnumber ?? null,
                'emp_country'           => isset($advisory->people->pais->name) ? strtoupper($advisory->people->pais->name) : 'PERU',
                'emp_birth'             => $advisory->people->birthday ? \Carbon\Carbon::parse($advisory->people->birthday)->format('d/m/Y') : null,
                // 'emp_age'               => $advisory->people->birthday ? \Carbon\Carbon::parse($advisory->people->birthday)->age : null,
                'emp_lastname'          => $advisory->people->lastname,
                'emp_middlename'        => $advisory->people->middlename,
                'emp_name'              => $advisory->people->name,
                'emp_gender'            => $advisory->people->gender->name == 'FEMENINO' ? 'F' : 'M',
                'emp_discapabilities'   => $advisory->people->sick ? strtoupper($advisory->people->sick) : null,
                'emp_soons'             => $advisory->people->hasSoon ?? null,
                'emp_phone'             => $advisory->people->phone,
                'emp_email'             => $advisory->people->email ? strtolower($advisory->people->email) : '-',

                'supervisor'            => isset($advisory->supervisor->supervisorUser->profile) ? strtoupper($advisory->supervisor->supervisorUser->profile->lastname . ' ' . $advisory->supervisor->supervisorUser->profile->middlename . ' ' . $advisory->supervisor->supervisorUser->profile->name) : null,

                'city'                  => $advisory->city->name ?? null,
                'province'              => $advisory->province->name ?? null,
                'district'              => $advisory->district->name ?? null,
                'ruc'                   => $advisory->ruc ?? null,
                'econimic_service'      => $advisory->economicsector->name ?? null,
                'activity_comercial'    => $advisory->comercialactivity->name ?? null,
                'component'             => $advisory->component->name ?? null,
                'theme'                 => strtoupper($advisory->theme->name) ?? null,
                'observations'          => $advisory->observations ?? null,
                'modality'              => $advisory->modality->name ?? null
            ]);
        }

        return Excel::download(new AsesoriasExport($advisories), 'asesorias.xlsx');
    }


    public function exportFormalizationsRuc10(Request $request)
    {
        $filters = [
            'asesor'    => $request->input('asesor'),
            'name'      => $request->input('name'),
            'dateStart' => $request->input('dateStart'),
            'dateEnd'   => $request->input('dateEnd'),
            'year'      => $request->input('year'),
        ];

        $userRole = getUserRole();
        $roleIds  = $userRole['role_id'];
        $userId   = $userRole['user_id'];

        $query = Formalization10::query();

        if (in_array(1, $roleIds) || $userId === 1) {
            $query->withFormalizationRangeDate($filters);
        } elseif (in_array(2, $roleIds) || in_array(7, $roleIds)) {
            $query->ByUserId($userId)->withFormalizationRangeDate($filters);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $index = 1;
        $fs10 = collect();

        foreach ($query->cursor() as $f10) {
            $fs10->push([
                'index'                 => $index++,
                'date'                  => $f10->created_at->format('d/m/Y'),
                'asesor'                => isset($f10->user->profile) ? strtoupper($f10->user->profile->lastname . ' ' . $f10->user->profile->middlename . ' ' . $f10->user->profile->name) : null,
                'asesor_cde_city'       => $f10->sede->city ?? null,
                'asesor_cde_province'   => $f10->sede->province ?? null,
                'asesor_cde_district'   => $f10->sede->district ?? null,

                'emp_document_type'     => $f10->people->typedocument->avr ?? null,
                'emp_document_number'   => $f10->people->documentnumber ?? null,
                'emp_country'           => isset($f10->people->pais->name) ? strtoupper($f10->people->pais->name) : 'PERU',
                'emp_birth'             => $f10->people->birthday ? \Carbon\Carbon::parse($f10->people->birthday)->format('d/m/Y') : null,
                // 'emp_age'               => $f10->people->birthday ? \Carbon\Carbon::parse($f10->people->birthday)->age : null,
                'emp_lastname'          => $f10->people->lastname,
                'emp_middlename'        => $f10->people->middlename,
                'emp_name'              => $f10->people->name,
                'emp_gender'            => $f10->people->gender->name == 'FEMENINO' ? 'F' : 'M',
                'emp_discapabilities'   => $f10->people->sick ? strtoupper($f10->people->sick) : null,
                'emp_soons'             => $f10->people->hasSoon ?? null,
                'emp_phone'             => $f10->people->phone,
                'emp_email'             => $f10->people->email ? strtolower($f10->people->email) : '-',

                'type_formalization'    => 'PPNN 10',

                'supervisor'            => isset($f10->supervisor->supervisorUser->profile) ? strtoupper($f10->supervisor->supervisorUser->profile->lastname . ' ' . $f10->supervisor->supervisorUser->profile->middlename . ' ' . $f10->supervisor->supervisorUser->profile->name) : null,

                'city'                  => $f10->city->name ?? null,
                'province'              => $f10->province->name ?? null,
                'district'              => $f10->district->name ?? null,
                'address'               => $f10->address ?? null,
                'ruc'                   => $f10->ruc ?? null,

                'econimic_sector'       => $f10->economicsector->name ?? null,
                'activity_comercial'    => $f10->comercialactivity->name ?? null,
                'detail_tramit'         => $f10->detailprocedure->name ?? null,
                'modality'              => $f10->modality->name ?? null,
            ]);
        }

        return Excel::download(new FormalizationRUC10Export($fs10), 'formalizaciones10-pnte.xlsx');
    }



    public function exportFormalizationsRuc20(Request $request)
    {
        $filters = [
            'asesor'    => $request->input('asesor'),
            'name'      => $request->input('name'),
            'dateStart' => $request->input('dateStart'),
            'dateEnd'   => $request->input('dateEnd'),
            'year'      => $request->input('year'),
        ];

        $userRole = getUserRole();
        $roleIds  = $userRole['role_id'];
        $userId   = $userRole['user_id'];

        $query = Formalization20::query();

        if (in_array(1, $roleIds) || $userId === 1) {
            $query->withFormalizationRangeDate($filters);
        } elseif (in_array(2, $roleIds) || in_array(7, $roleIds)) {
            $query->ByUserId($userId)->withFormalizationRangeDate($filters);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $index = 1;
        $fs20 = collect();

        foreach ($query->cursor() as $f20) {
            $fs20->push([
                'index'                 => $index++,
                'date'                  => $f20->created_at->format('d/m/Y'),
                'asesor'                => isset($f20->user->profile) ? strtoupper($f20->user->profile->lastname . ' ' . $f20->user->profile->middlename . ' ' . $f20->user->profile->name) : null,
                'asesor_cde_city'       => $f20->sede->city ?? null,
                'asesor_cde_province'   => $f20->sede->province ?? null,
                'asesor_cde_district'   => $f20->sede->district ?? null,

                'emp_document_type'     => $f20->people->typedocument->avr ?? null,
                'emp_document_number'   => $f20->people->documentnumber ?? null,
                'emp_country'           => isset($f20->people->pais->name) ? strtoupper($f20->people->pais->name) : 'PERU',
                'emp_birth'             => $f20->people->birthday ? \Carbon\Carbon::parse($f20->people->birthday)->format('d/m/Y') : null,
                // 'emp_age'               => $f20->people->birthday ? \Carbon\Carbon::parse($f20->people->birthday)->age : null,
                'emp_lastname'          => $f20->people->lastname,
                'emp_middlename'        => $f20->people->middlename,
                'emp_name'              => $f20->people->name,
                'emp_gender'            => $f20->people->gender->name == 'FEMENINO' ? 'F' : 'M',
                'emp_discapabilities'   => $f20->people->sick ? strtoupper($f20->people->sick) : null,
                'emp_soons'             => $f20->people->hasSoon ?? null,
                'emp_phone'             => $f20->people->phone,
                'emp_email'             => $f20->people->email ? strtolower($f20->people->email) : '-',

                'type_formalization'    => 'PPJJ 20',

                'supervisor'            => isset($f20->supervisor->supervisorUser->profile) ? strtoupper($f20->supervisor->supervisorUser->profile->lastname . ' ' . $f20->supervisor->supervisorUser->profile->middlename . ' ' . $f20->supervisor->supervisorUser->profile->name) : null,

                'city'                  => $f20->city->name ?? null,
                'province'              => $f20->province->name ?? null,
                'district'              => $f20->district->name ?? null,
                'address'               => $f20->address ?? null,
                'ruc'                   => $f20->ruc ?? null,

                'econimic_sector'       => $f20->economicsector->name ?? null,
                'activity_comercial'    => $f20->comercialactivity->name ?? null,
                'date_reception'        => $f20->dateReception ? \Carbon\Carbon::parse($f20->dateReception)->format('d/m/Y') : null,
                'date_tramite'          => $f20->dateTramite ? \Carbon\Carbon::parse($f20->dateTramite)->format('d/m/Y') : null,
                'name_mype'             => strtoupper($f20->nameMype),
                'type_regimen'          => $f20->regime->name,
                'bic'                   => $f20->isbic,
                'num_solicitud'         => $f20->numbernotary,
                'notaria'               => isset($f20->notary->name) ? strtoupper($f20->notary->name) : null,
                'type_aporte'           => optional($f20->typecapital)->name,
                'monto_capital'         => $f20->montocapital,
                'modality'              => $f20->modality->name ?? null
            ]);
        }

        return Excel::download(new FormalizationRUC20Export($fs20), 'f20-pnte.xlsx');
    }
}
