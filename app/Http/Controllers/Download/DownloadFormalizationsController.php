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
        $data = collect($request->all());

        $result = $data->map(function ($item, $index) {
            return [
                'index'                 => $index + 1,
                'date'                  => $item['date'],
                'asesor'                => $item['asesor'],
                'asesor_cde_city'       => $item['asesor_cde_city'],
                'asesor_cde_province'   => $item['asesor_cde_province'],
                'asesor_cde_district'   => $item['asesor_cde_district'],

                'emp_document_type'     => $item['emp_document_type'],
                'emp_document_number'   => $item['emp_document_number'],
                'emp_country'           => $item['emp_country'],
                'emp_birth'             => $item['emp_birth'],
                // 'emp_age'               => $item['emp_age'],
                'emp_lastname'          => $item['emp_lastname'],
                'emp_middlename'        => $item['emp_middlename'],
                'emp_name'              => $item['emp_name'],
                'emp_gender'            => $item['emp_gender'],
                'emp_discapabilities'   => $item['emp_discapabilities'],
                'emp_soons'             => $item['emp_soons'],
                'emp_phone'             => $item['emp_phone'],
                'emp_email'             => $item['emp_email'],

                'supervisor'            => $item['supervisor'],

                'city'                  => $item['city'],
                'province'              => $item['province'],
                'district'              => $item['district'],
                'ruc'                   => $item['ruc'],
                'econimic_service'      => $item['econimic_service'],
                'activity_comercial'    => $item['activity_comercial'],
                'component'             => $item['component'],
                'theme'                 => $item['theme'],
                'observations'          => $item['observations'],
                'modality'              => $item['modality'],
            ];
        });

        return Excel::download(new AsesoriasExport($result), 'asesorias.xlsx');
    }


    public function exportFormalizationsRuc10(Request $request)
    {
        $data = collect($request->all());

        $result = $data->map(function ($item, $index) {
            return [
                'index'                 => $index + 1,
                'date'                  => $item['date'],
                'asesor'                => $item['asesor'],
                'asesor_cde_city'       => $item['asesor_cde_city'],
                'asesor_cde_province'   => $item['asesor_cde_province'],
                'asesor_cde_district'   => $item['asesor_cde_district'],

                'emp_document_type'     => $item['emp_document_type'],
                'emp_document_number'   => $item['emp_document_number'],
                'emp_country'           => $item['emp_country'],
                'emp_birth'             => $item['emp_birth'],
                // 'emp_age'               => $item['emp_age'],
                'emp_lastname'          => $item['emp_lastname'],
                'emp_middlename'        => $item['emp_middlename'],
                'emp_name'              => $item['emp_name'],
                'emp_gender'            => $item['emp_gender'],
                'emp_discapabilities'   => $item['emp_discapabilities'],
                'emp_soons'             => $item['emp_soons'],
                'emp_phone'             => $item['emp_phone'],
                'emp_email'             => $item['emp_email'],

                'tipo_formalization'    => 'PPNN (RUC 10)',

                'supervisor'            => $item['supervisor'],

                'city'                  => $item['city'],
                'province'              => $item['province'],
                'district'              => $item['district'],
                'address'               => $item['address'],
                'ruc'                   => $item['ruc'],

                'econimic_sector'       => $item['econimic_sector'],
                'activity_comercial'    => $item['activity_comercial'],
                'detail_tramit'         => $item['detail_tramit'],
                'modality'              => $item['modality']
            ];
        });

        return Excel::download(new FormalizationRUC10Export($result), 'formalizaciones10-pnte.xlsx');
    }



    public function exportFormalizationsRuc20(Request $request)
    {
        $data = collect($request->all());

        $result = $data->map(function ($item, $index) {
            return [
                'index'                 => $index + 1,
                'date'                  => $item['date'],
                'asesor'                => $item['asesor'],
                'asesor_cde_city'       => $item['asesor_cde_city'],
                'asesor_cde_province'   => $item['asesor_cde_province'],
                'asesor_cde_district'   => $item['asesor_cde_district'],

                'emp_document_type'     => $item['emp_document_type'],
                'emp_document_number'   => $item['emp_document_number'],
                'emp_country'           => $item['emp_country'],
                'emp_birth'             => $item['emp_birth'],
                // 'emp_age'               => $item['emp_age'],
                'emp_lastname'          => $item['emp_lastname'],
                'emp_middlename'        => $item['emp_middlename'],
                'emp_name'              => $item['emp_name'],
                'emp_gender'            => $item['emp_gender'],
                'emp_discapabilities'   => $item['emp_discapabilities'],
                'emp_soons'             => $item['emp_soons'],
                'emp_phone'             => $item['emp_phone'],
                'emp_email'             => $item['emp_email'],

                'tipo_formalization'    => 'PPJJ (RUC 20)',

                'supervisor'            => $item['supervisor'],

                'city'                  => $item['city'],
                'province'              => $item['province'],
                'district'              => $item['district'],
                'address'               => $item['address'],
                'ruc'                   => $item['ruc'],

                'econimic_sector'       => $item['econimic_sector'],
                'activity_comercial'    => $item['activity_comercial'],
                'date_reception'        => $item['date_reception'],
                'date_tramite'          => $item['date_tramite'],
                'name_mype'             => $item['name_mype'],
                'type_regimen'          => $item['type_regimen'],
                'bic'                   => $item['bic'],
                'num_solicitud'         => $item['num_solicitud'],
                'notaria'               => $item['notaria'],
                'type_aporte'           => $item['type_aporte'],
                'monto_capital'         => $item['monto_capital'],
                'modality'              => $item['modality']
            ];
        });

        return Excel::download(new FormalizationRUC20Export($result), 'asesorias-pnte.xlsx');
    }
}
