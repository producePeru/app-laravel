<?php

namespace App\Observers;

use App\Models\Formalization10;
use App\Models\Reason;
use Illuminate\Support\Facades\Auth;

class Formalization10Observer
{
    public function updated(Formalization10 $f10): void
    {
        $changes = $f10->getChanges(); // Solo los campos modificados
        $original = $f10->getOriginal();

        $fieldTranslations = [
            'economicsector_id'     => 'Sector Económico',
            'comercialactivity_id'  => 'Actividad Comercial',
            'detailprocedure_id'    => 'Detalle del trámite',
            'modality_id'           => 'Modalidad de Atención',
            'ruc'                   => 'RUC',
            'city_id'               => 'Región',
            'province_id'           => 'Provincia',
            'district_id'           => 'Distrito',
            'address'               => 'Dirección'
        ];

        foreach ($changes as $field => $newValue) {
            if ($field === "updated_at") continue; // ignoramos campo técnico

            $translatedField = $fieldTranslations[$field] ?? $field; // si no está traducido, usa el original

            // Si el valor original es null, mostrar '–'
            $originalValue = is_null($original[$field]) ? '–' : $original[$field];

            // Si el valor nuevo es null, mostrar '–'
            $newValueDisplay = is_null($newValue) ? '–' : $newValue;

            Reason::create([
                'table_name'  => 'f10',
                'row_id'      => $f10->id,
                'description' => "{$translatedField} (De: {$originalValue} A: {$newValueDisplay})",
                'action'      => 'u', // update
                'user_id'     => Auth::id() ?? 1,
            ]);
        }
    }
}
