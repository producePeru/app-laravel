<?php

namespace App\Observers;

use App\Models\Advisory;
use App\Models\Reason;
use Illuminate\Support\Facades\Auth;

class AdvisoryObserver
{
    public function updated(Advisory $advisory): void
    {
        $changes = $advisory->getChanges(); // Solo los campos modificados
        $original = $advisory->getOriginal();

        $fieldTranslations = [
            'economicsector_id'     => 'Sector Económico',
            'comercialactivity_id'  => 'Actividad Comercial',
            'component_id'          => 'Componente',
            'theme_id'              => 'Tema del componente',
            'modality_id'           => 'Modalidad de Atención',
            'ruc'                   => 'RUC',
            'city_id'               => 'Región',
            'province_id'           => 'Provincia',
            'district_id'           => 'Distrito'
        ];

        foreach ($changes as $field => $newValue) {
            if ($field === "updated_at") continue; // ignoramos campo técnico

            $translatedField = $fieldTranslations[$field] ?? $field; // si no está traducido, usa el original

            // Si el valor original es null, mostrar '–'
            $originalValue = is_null($original[$field]) ? '–' : $original[$field];

            // Si el valor nuevo es null, mostrar '–'
            $newValueDisplay = is_null($newValue) ? '–' : $newValue;

            Reason::create([
                'table_name'  => 'asesoria',
                'row_id'      => $advisory->id,
                'description' => "{$translatedField} (De: {$originalValue} A: {$newValueDisplay})",
                'action'      => 'u', // update
                'user_id'     => Auth::id() ?? 1,
            ]);
        }
    }
}
