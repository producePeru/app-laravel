<?php

namespace App\Http\Controllers\Upload;

use App\Http\Controllers\Controller;
use App\Models\ActividadPnte;
use App\Models\Empresario;
use App\Models\EmpresarioActividad;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class UploadController extends Controller
{
    /**
     * Corrige datos del Empresario desde un Excel.
     *
     * Excel esperado (cabeceras):
     * numero_dni | apellido_paterno | apellido_materno | nombres |
     * fecha_nacimiento | ruc | razon_social | nombre_comercial
     *
     * Flujo por fila:
     *  1. Busca el registro en empresario_actividad por slug + numero_dni.
     *  2. Con ese registro ubica al empresario (empresario_id) y actualiza sus columnas.
     *  3. Sincroniza el numero_dni de la fila pivote.
     */
    public function sedCorreccion(Request $request)
    {
        $request->validate([
            'slug' => 'required|string',
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $slug = trim($request->input('slug'));

        $actividad = ActividadPnte::where('slug', $slug)->first();
        if (! $actividad) {
            return response()->json([
                'status' => 404,
                'message' => 'Evento no encontrado para el slug indicado.',
            ], 404);
        }

        try {
            ini_set('memory_limit', '512M');
            set_time_limit(300);

            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            if (empty($rows)) {
                return response()->json([
                    'status' => 422,
                    'message' => 'El archivo no tiene filas.',
                ], 422);
            }

            // ── Mapa de cabeceras → campo del modelo ─────────────────────
            $aliases = [
                'numero_dni' => ['numero_dni', 'numero dni', 'nro documento', 'nro_documento', 'nrodocumento', 'dni', 'numerodni', 'numero_dni_empresario', 'numero de documento', 'n° documento', 'nro dni'],
                'apellido_paterno' => ['apellido_paterno', 'apellido paterno', 'apellidopaterno', 'paterno'],
                'apellido_materno' => ['apellido_materno', 'apellido materno', 'apellidomaterno', 'materno'],
                'nombres' => ['nombres', 'nombre', 'names'],
                'fecha_nacimiento' => ['fecha_nacimiento', 'fecha nacimiento', 'fechanacimiento', 'fecha_nac', 'fnacimiento', 'f_nacimiento'],
                'ruc' => ['ruc'],
                'razon_social' => ['razon_social', 'razon social', 'razonsocial', 'razón social'],
                'nombre_comercial' => ['nombre_comercial', 'nombre comercial', 'nombrecomercial'],
            ];

            $norm = fn ($v) => strtolower(trim((string) $v));

            $firstRow = reset($rows);
            $colMap = []; // letra Excel => campo
            foreach ((array) $firstRow as $letter => $cell) {
                $c = $norm($cell);
                foreach ($aliases as $field => $names) {
                    if (in_array($c, $names, true)) {
                        $colMap[$letter] = $field;
                        break;
                    }
                }
            }

            $hasHeader = isset(array_flip($colMap)['numero_dni']);
            if ($hasHeader) {
                array_shift($rows);
            } else {
                // Fallback posicional: A..H en el orden indicado por el usuario.
                $colMap = [
                    'A' => 'numero_dni',
                    'B' => 'apellido_paterno',
                    'C' => 'apellido_materno',
                    'D' => 'nombres',
                    'E' => 'fecha_nacimiento',
                    'F' => 'ruc',
                    'G' => 'razon_social',
                    'H' => 'nombre_comercial',
                ];
                // Si la primera fila es texto de cabecera (A no numérico), omitirla.
                $a = trim((string) ($firstRow['A'] ?? ''));
                if ($a !== '' && ! preg_match('/^\d+$/', preg_replace('/\D/', '', $a))) {
                    $maybeHeader = $norm($a);
                    if (str_contains($maybeHeader, 'dni') || str_contains($maybeHeader, 'documento') || str_contains($maybeHeader, 'numero')) {
                        array_shift($rows);
                    }
                }
            }

            $actualizados = 0;
            $sinCambios = 0;
            $noEncontrados = 0;
            $errores = [];
            $total = 0;

            $empiezaEn = $hasHeader ? 2 : 1;
            $pos = 0;
            DB::transaction(function () use ($rows, $colMap, $slug, $empiezaEn, &$pos, &$actualizados, &$sinCambios, &$noEncontrados, &$errores, &$total) {
                foreach ($rows as $row) {
                    $filaNum = $empiezaEn + $pos;
                    $pos++;

                    $filaTieneDatos = collect($row)
                        ->filter(fn ($v) => ! is_null($v) && trim((string) $v) !== '')
                        ->isNotEmpty();
                    if (! $filaTieneDatos) {
                        continue;
                    }
                    $total++;

                    $data = [];
                    foreach ($colMap as $letter => $field) {
                        $data[$field] = isset($row[$letter]) ? trim((string) $row[$letter]) : null;
                        if ($data[$field] === '') {
                            $data[$field] = null;
                        }
                    }

                    $dni = isset($data['numero_dni'])
                        ? trim(preg_replace('/\s+/', '', (string) $data['numero_dni']))
                        : null;

                    if (empty($dni)) {
                        $errores[] = ['fila' => $filaNum, 'error' => 'numero_dni vacío', 'valor' => null];
                        continue;
                    }

                    // 1. Registro pivote del evento por slug + numero_dni.
                    $registro = EmpresarioActividad::where('slug', $slug)
                        ->where('numero_dni', $dni)
                        ->first();

                    // Fallback: ubicar empresario y luego el pivote por empresario_id.
                    $empresario = null;
                    if ($registro && $registro->empresario_id) {
                        $empresario = Empresario::find($registro->empresario_id);
                    }
                    if (! $empresario) {
                        $empresario = Empresario::where('numero_dni', $dni)->first();
                    }
                    if (! $registro && $empresario) {
                        $registro = EmpresarioActividad::where('slug', $slug)
                            ->where('empresario_id', $empresario->id)
                            ->first();
                    }

                    if (! $registro || ! $empresario) {
                        $noEncontrados++;
                        $errores[] = ['fila' => $filaNum, 'error' => 'No inscrito en este evento', 'valor' => $dni];
                        continue;
                    }

                    // 2. Armar actualización solo con celdas no vacías.
                    $update = [];
                    if (! empty($data['apellido_paterno'])) {
                        $update['apellido_paterno'] = mb_strtoupper($data['apellido_paterno'], 'UTF-8');
                    }
                    if (! empty($data['apellido_materno'])) {
                        $update['apellido_materno'] = mb_strtoupper($data['apellido_materno'], 'UTF-8');
                    }
                    if (! empty($data['nombres'])) {
                        $update['nombres'] = mb_strtoupper($data['nombres'], 'UTF-8');
                    }
                    if (! empty($data['fecha_nacimiento'])) {
                        $fecha = $this->parseFecha($data['fecha_nacimiento'], $row, $colMap);
                        if ($fecha) {
                            $update['fecha_nacimiento'] = $fecha;
                        } else {
                            $errores[] = ['fila' => $filaNum, 'error' => 'fecha_nacimiento inválida', 'valor' => $data['fecha_nacimiento']];
                        }
                    }
                    if (! empty($data['ruc'])) {
                        $update['ruc'] = preg_replace('/\s+/', '', $data['ruc']);
                    }
                    if (! empty($data['razon_social'])) {
                        $update['razon_social'] = trim($data['razon_social']);
                    }
                    if (! empty($data['nombre_comercial'])) {
                        $update['nombre_comercial'] = trim($data['nombre_comercial']);
                    }

                    // 3. Actualizar fila del empresario + sincronizar dni del pivote.
                    if (! empty($update)) {
                        $empresario->fill($update);
                        if ($empresario->isDirty()) {
                            $empresario->save();
                            $actualizados++;
                        } else {
                            $sinCambios++;
                        }
                    } else {
                        $sinCambios++;
                    }

                    if ($registro->numero_dni !== $dni) {
                        $registro->numero_dni = $dni;
                        $registro->save();
                    }
                }
            });

            return response()->json([
                'status' => 200,
                'message' => 'Corrección completada.',
                'slug' => $slug,
                'total_procesados' => $total,
                'actualizados' => $actualizados,
                'sin_cambios' => $sinCambios,
                'no_encontrados' => $noEncontrados,
                'errores' => array_slice($errores, 0, 50),
                'total_errores' => count($errores),
            ]);
        } catch (\Throwable $e) {
            Log::error('sedCorreccion: '.$e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Error al procesar el archivo.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Parsea fecha desde string o serial de Excel. Retorna Y-m-d o null.
     */
    private function parseFecha(?string $value, array $row, array $colMap): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        // Serial de Excel (p. ej. 45123).
        if (is_numeric($value) && (float) $value > 20000 && (float) $value < 60000) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        // Conocer la celda de fecha para evaluar serial real si vino formateada.
        foreach ($colMap as $letter => $field) {
            if ($field === 'fecha_nacimiento' && isset($row[$letter]) && is_numeric($row[$letter])) {
                $raw = (float) $row[$letter];
                if ($raw > 20000 && $raw < 60000) {
                    try {
                        return ExcelDate::excelToDateTimeObject($raw)->format('Y-m-d');
                    } catch (\Throwable $e) {
                        // sigue al parseo por string
                    }
                }
            }
        }

        $candidates = [$value, str_replace('/', '-', $value), str_replace('.', '-', $value)];
        $formats = ['Y-m-d', 'd-m-Y', 'd-m-y', 'm-d-Y', 'Y/m/d'];

        foreach ($candidates as $candidate) {
            foreach ($formats as $format) {
                try {
                    $dt = Carbon::createFromFormat($format, $candidate);
                    if ($dt && $dt->format($format) === $candidate) {
                        return $dt->format('Y-m-d');
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }
            try {
                return Carbon::parse($candidate)->format('Y-m-d');
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }
}
