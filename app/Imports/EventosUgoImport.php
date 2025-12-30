<?php

namespace App\Imports;

use App\Models\Attendance;
use App\Models\City;
use App\Models\Province;
use App\Models\District;
use App\Models\User;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Facades\Log;

class EventosUgoImport implements ToModel, WithStartRow, WithChunkReading
{
    private $cityMap;
    private $provinceMap;
    private $districtMap;
    private $userMap;

    public function __construct()
    {
        // Cachear ciudades, provincias y distritos al inicio
        $this->cityMap = City::pluck('name', 'id')->mapWithKeys(fn($name, $id) => [strtolower($name) => $id]);
        $this->provinceMap = Province::pluck('name', 'id')->mapWithKeys(fn($name, $id) => [strtolower($name) => $id]);
        $this->districtMap = District::pluck('name', 'id')->mapWithKeys(fn($name, $id) => [strtolower($name) => $id]);

        // Cachear usuarios por nombre completo
        $this->userMap = User::get(['id', 'name'])->keyBy('name');
    }

    /**
     * @param array $row
     * @return Attendance|null
     */
    public function model(array $row)
    {
        try {
            // Omitir encabezado
            if (!isset($row[1]) || $row[1] === 'Nombre de actividad') return null;



            // Validar si existe la columna "id" en el Excel
            if (!isset($row[11])) {
                Log::warning("Fila sin ID válido", ['fila' => $row]);
                return null; // No procesar si falta la columna "id"
            }

            // Obtener el ID del Excel
            $excelId = trim($row[11]);

            // Verificar si el ID ya existe en la base de datos
            if (Attendance::where('id', $excelId)->exists()) {
                Log::info("Registro con ID {$excelId} ya existe en la base de datos.", ['fila' => $row]);
                return null; // No insertar si ya existe
            }



            // Generar slug único
            $slugBase = Str::slug(trim($row[1]));
            $slug = $this->generateUniqueSlug($slugBase);

            // Parsear fechas
            $startDate = $this->parseDate($row[2]);
            $endDate = $this->parseDate($row[3]);

            // Modality P/V
            $modality = strtoupper(trim($row[4])) === 'PRESENCIAL' ? 'p' : 'v';

            // Buscar ciudad/provincia/distrito por coincidencia parcial
            $cityId = $this->getIdFromMap($this->cityMap, $row[5]);
            $provinceId = $this->getIdFromMap($this->provinceMap, $row[6]);
            $districtId = $this->getIdFromMap($this->districtMap, $row[7]);

            // Buscar asesor por coincidencia exacta o parcial
            $asesorId = $this->findUserId($row[9]);

            return new Attendance([
                'id'              => $excelId,
                'eventsoffice_id' => 5,
                'title'           => trim($row[1]),
                'slug'            => $slug,
                'startDate'       => $startDate,
                'endDate'         => $endDate,
                'modality'        => $modality,
                'city_id'         => $cityId,
                'province_id'     => $provinceId,
                'district_id'     => $districtId,
                'address'         => trim($row[8]),
                'asesorId'        => $asesorId,
                'description'     => trim($row[10]),
            ]);
        } catch (\Exception $e) {
            Log::error("Error en fila", [
                'fila' => $row,
                'error' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine()
            ]);
            return null;
        }
    }

    /**
     * Genera un slug único
     */
    private function generateUniqueSlug(string $slugBase): string
    {
        $slug = $slugBase;
        $count = 1;
        while (Attendance::where('slug', $slug)->exists()) {
            $slug = "{$slugBase}-{$count}";
            $count++;
        }
        return $slug;
    }

    /**
     * Busca ID en mapa pre-cargado usando coincidencia parcial
     */
    private function getIdFromMap($map, $value): ?int
    {
        $value = strtolower(trim($value));
        foreach ($map as $name => $id) {
            if (str_contains($name, $value)) {
                return $id;
            }
        }
        return null;
    }

    /**
     * Busca usuario por nombre completo (exacto o parcial)
     */
    private function findUserId(string $fullName)
    {
        // Normalizar el nombre completo (quitar espacios extras y pasar a minúsculas)
        $normalizedFullName = strtolower(trim($fullName));

        // Dividir el nombre completo en partes
        $parts = explode(' ', $normalizedFullName);

        // Construir una consulta dinámica para buscar coincidencias parciales
        $query = User::query();

        foreach ($parts as $part) {
            if (!empty($part)) {
                $query->where(function ($q) use ($part) {
                    $q->whereRaw("LOWER(name) LIKE ?", ["%" . $part . "%"])
                        ->orWhereRaw("LOWER(lastname) LIKE ?", ["%" . $part . "%"])
                        ->orWhereRaw("LOWER(middlename) LIKE ?", ["%" . $part . "%"]);
                });
            }
        }

        // Ejecutar la consulta y obtener el primer resultado
        return $query->first()?->id ?? null;
    }

    /**
     * Iniciar desde fila 2
     */
    public function startRow(): int
    {
        return 2;
    }

    /**
     * Tamaño de chunk
     */
    public function chunkSize(): int
    {
        return 200; // Procesa 200 filas a la vez
    }

    /**
     * Convierte fecha Excel o cadena a formato Y-m-d
     */
    private function parseDate(string $date): ?string
    {
        try {
            if (is_numeric($date)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date)->format('Y-m-d');
            }
            return date('Y-m-d', strtotime($date));
        } catch (\Exception $e) {
            return null;
        }
    }
}
