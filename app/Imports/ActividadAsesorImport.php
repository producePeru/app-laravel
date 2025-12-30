<?php

namespace App\Imports;

use App\Models\AttendanceList;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\ToCollection;

class ActividadAsesorImport implements ToCollection, WithHeadingRow
{
    private $attendanceId;
    private $slug;
    private $totalRows = 0;

    public function __construct($attendanceId, $slug)
    {
        $this->attendanceId = $attendanceId;
        $this->slug = $slug;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $r) {
            $this->totalRows++;

            $data = [
                'typedocument_id'   => $this->mapTipoDocumento($r['tipo_de_documento'] ?? ''),
                'documentnumber'    => $r['numero_de_documento'] ?? null,
                'name'              => $r['nombres'] ?? null,
                'lastname'          => $r['apellido_paterno'] ?? null,
                'middlename'        => $r['apellido_materno'] ?? null,
                'gender_id'         => $this->mapGenero($r['genero'] ?? ''),
                'sick'              => strtolower($r['sick'] ?? 'no'),
                'email'             => $r['email'] ?? null,
                'phone'             => $r['celular'] ?? null,
                'ruc'               => $r['ruc'] ?? null,
                'socialReason'      => $r['razon_social'] ?? null,
                'economicsector_id' => $this->mapSector($r['sector_economico'] ?? ''),
                'comercialActivity' => $r['actividad_comercial'] ?? null,
                'slug'              => $this->slug,
                'attendancelist_id' => $this->attendanceId,
            ];

            $exists = AttendanceList::where('attendancelist_id', $this->attendanceId)
                ->where('documentnumber', $data['documentnumber'])
                ->exists();

            if (!$exists) {
                AttendanceList::create($data);
            }
        }
    }

    public function getTotalRows()
    {
        return $this->totalRows;
    }

    private function mapGenero($value)
    {
        return match (strtoupper(trim($value))) {
            'M' => 1,
            'F' => 2,
            default => null,
        };
    }

    private function mapSector($value)
    {
        return match (strtoupper(trim($value))) {
            'INDUSTRIA' => 1,
            'SERVICIOS' => 2,
            'COMERCIO'  => 3,
            default => null,
        };
    }

    private function mapTipoDocumento($value)
    {
        return match (strtoupper(trim($value))) {
            'DNI' => 1,
            'CARNET EXTRANJERIA' => 2,
            'PASAPORTE' => 3,
            'PERMISO TEMPORAL' => 4,
            default => null,
        };
    }
}
