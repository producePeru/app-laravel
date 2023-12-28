<?php

namespace App\Imports;

use App\Models\Mype;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class QueuedImport implements ToModel, WithHeadingRow, WithChunkReading, WithBatchInserts, ShouldQueue
{

    use Importable;
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {

        $mype = new Mype([
            'ruc' => $row['ruc'],
            'social_reason' => $row['razon_social'],
            'category' => $row['rubro'],
            'type' => $row['tipo'],
            'department' => $row['departamento'],
            'district' => $row['distrito'],
            'name_complete' => $row['nombres_apellidos'],
            'dni_number' => $row['dni'],
            'sex' => $row['sexo'],
            'phone' => $row['telefono'],
            'email' => $row['email'],
            'registration_date' => $row['fecha_registro']
        ]);

        $mype->save();
    }

    public function headingRow(): int
    {
        return 3;
    }

    public function batchSize(): int
    {
        return 100; // Puedes ajustar según tus necesidades
    }


    public function chunkSize(): int
    {
        return 5000;
    }
}
