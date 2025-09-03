<?php

namespace App\Observers;

use App\Models\Notification;
use App\Models\People;
use App\Models\Reason;
use Illuminate\Support\Facades\Auth;

class PeopleObserver
{
    /**
     * Handle the People "created" event.
     */
    public function created(People $people): void
    {
        // Reason::create([
        //     'table_name'  => 'people',
        //     'row_id'      => $people->id,
        //     'description' => 'Nuevo registro creado',
        //     'action'      => 'c',
        //     'user_id'     => Auth::id() ?? 1,
        // ]);
    }

    /**
     * Handle the People "updated" event.
     */
    public function updated(People $people): void
    {
        $changes = $people->getChanges(); // Solo los campos modificados
        $original = $people->getOriginal();

        $fieldTranslations = [
            'typedocument_id'   => 'Tipo de Documento',
            'documentnumber'    => 'Número de Documento',
            'lastname'          => 'Apellido Paterno',
            'middlename'        => 'Apellido Materno',
            'name'              => 'Nombre',
            'phone'             => 'Celular',
            'email'             => 'Correo Electrónico',
            'birthday'          => 'Fecha de Nacimiento',
            'sick'              => 'Discapacidad',
            'hasSoon'           => 'Tiene hijos',
            'country_id'        => 'País',
            'city_id'           => 'Ciudad',
            'province_id'       => 'Provincia',
            'district_id'       => 'Distrito',
            'address'           => 'Dirección',
            'gender_id'         => 'Género'
        ];

        foreach ($changes as $field => $newValue) {
            if ($field === "updated_at") continue; // ignoramos campo técnico

            $translatedField = $fieldTranslations[$field] ?? $field; // si no está traducido, usa el original

            // Si el valor original es null, mostrar '–'
            $originalValue = is_null($original[$field]) ? '–' : $original[$field];

            // Si el valor nuevo es null, mostrar '–'
            $newValueDisplay = is_null($newValue) ? '–' : $newValue;

            Reason::create([
                'table_name'  => 'people',
                'row_id'      => $people->id,
                'description' => "{$translatedField} (De: {$originalValue} A: {$newValueDisplay})",
                'action'      => 'u', // update
                'user_id'     => Auth::id() ?? 1,
            ]);

            Notification::query()->increment('count');
        }
    }

    /**
     * Handle the People "deleted" event.
     */
    public function deleted(People $people): void
    {
        // Reason::create([
        //     'table_name'  => 'people',
        //     'row_id'      => $people->id,
        //     'description' => 'Registro eliminado',
        //     'action'      => 'd',
        //     'user_id'     => Auth::id() ?? 1,
        // ]);
    }

    /**
     * Handle the People "restored" event.
     */
    public function restored(People $people): void
    {
        //
    }

    /**
     * Handle the People "force deleted" event.
     */
    public function forceDeleted(People $people): void
    {
        //    php artisan make:observer AdvisoryObserver --model=People
        // En AppServiceProvider.php → boot():
    }
}
