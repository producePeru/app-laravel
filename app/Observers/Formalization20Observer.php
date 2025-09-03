<?php

namespace App\Observers;

use App\Models\Reason;
use App\Models\Formalization20;
use Illuminate\Support\Facades\Auth;

class Formalization20Observer
{
    public function updated(Formalization20 $f20): void
    {
        $changes = $f20->getChanges(); // Solo los campos modificados
        $original = $f20->getOriginal();

        $fieldTranslations = [
            'ruc'                   => 'RUC',
            'regime_id'             => 'Tipo de Regimen societario',
            'nameMype'              => 'Nombre de la Empresa',
            'city_id'               => 'Región',
            'province_id'           => 'Provincia',
            'district_id'           => 'Distrito',
            'address'               => 'Dirección',
            'modality_id'           => 'Modalidad de Atención',
            'economicsector_id'     => 'Sector Económico',
            'comercialactivity_id'  => 'Actividad Comercial',
            'numbernotary'          => 'Nro. solicitud constancia',
            'notary_id'             => 'Notaría',
            'dateReception'         => 'Fecha de recepción PNTE',
            'datetramite'           => 'Fecha de trámite SID SUNARP',
            'isbic'                 => '¿Es BIC?',
            'montocapital'          => 'Monto de capital social',
            'typecapital_id'        => 'Tipo de aporte de capital'
        ];

        foreach ($changes as $field => $newValue) {
            if ($field === "updated_at") continue; // ignoramos campo técnico

            $translatedField = $fieldTranslations[$field] ?? $field; // si no está traducido, usa el original

            // Si el valor original es null, mostrar '–'
            $originalValue = is_null($original[$field]) ? '–' : $original[$field];

            // Si el valor nuevo es null, mostrar '–'
            $newValueDisplay = is_null($newValue) ? '–' : $newValue;

            Reason::create([
                'table_name'  => 'f20',
                'row_id'      => $f20->id,
                'description' => "{$translatedField} (De: {$originalValue} A: {$newValueDisplay})",
                'action'      => 'u', // update
                'user_id'     => Auth::id() ?? 1,
            ]);
        }
    }
}
