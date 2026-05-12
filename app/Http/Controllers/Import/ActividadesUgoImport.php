<?php

namespace App\Http\Controllers\Import;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Empresario;
use App\Models\EmpresarioActividad;
use App\Models\ActividadPnte;
use App\Models\EconomicSector;
use App\Models\Category;
use App\Models\Country;
use App\Models\City;
use App\Models\Province;
use App\Models\District;
use App\Models\Typedocument;
use App\Models\Gender;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class ActividadesUgoImport extends Controller
{
    public function importarInscritosEvento(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $actividad = ActividadPnte::where('slug', $slug)->firstOrFail();

        try {
            ini_set('memory_limit', '512M');
            set_time_limit(300);

            $file        = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet       = $spreadsheet->getActiveSheet();
            $rows        = $sheet->toArray(null, true, true, true);

            array_shift($rows);

            $registrados  = 0;
            $actualizados = 0;
            $errores      = [];

            DB::transaction(function () use (
                $rows,
                $actividad,
                $slug,
                &$registrados,
                &$actualizados,
                &$errores
            ) {
                $sectores   = EconomicSector::pluck('id', 'name')
                    ->mapWithKeys(fn($id, $name) => [strtoupper($name) => $id]);

                $rubros     = Category::pluck('id', 'name')
                    ->mapWithKeys(fn($id, $name) => [strtoupper($name) => $id]);

                $paises     = Country::pluck('id', 'name')
                    ->mapWithKeys(fn($id, $name) => [strtoupper($name) => $id]);

                $ciudades   = City::pluck('id', 'name')
                    ->mapWithKeys(fn($id, $name) => [strtoupper($name) => $id]);

                $provincias = Province::pluck('id', 'name')
                    ->mapWithKeys(fn($id, $name) => [strtoupper($name) => $id]);

                $distritos  = District::pluck('id', 'name')
                    ->mapWithKeys(fn($id, $name) => [strtoupper($name) => $id]);

                $tiposDocs  = Typedocument::pluck('id', 'name')
                    ->mapWithKeys(fn($id, $name) => [strtoupper($name) => $id]);

                $generos    = Gender::pluck('id', 'name')
                    ->mapWithKeys(fn($id, $name) => [strtoupper($name) => $id]);

                // ✅ Detectar duplicados ruc+numero_dni dentro del mismo archivo
                $combinacionesEnArchivo = [];

                foreach ($rows as $index => $row) {

                    $filaTieneDatos = collect($row)->filter(fn($v) => !is_null($v) && trim((string)$v) !== '')->isNotEmpty();
                    if (!$filaTieneDatos) continue;

                    $ruc       = trim((string)($row['A'] ?? ''));
                    $numeroDoc = trim((string)($row['M'] ?? ''));

                    if (empty($numeroDoc)) continue;

                    $clave = $ruc . '|' . $numeroDoc;

                    if (isset($combinacionesEnArchivo[$clave])) {
                        $filaNum      = $index + 2;
                        $filaOriginal = $combinacionesEnArchivo[$clave];
                        $errores[]    = "Fila {$filaNum}: RUC {$ruc} + documento {$numeroDoc} ya existe en la fila {$filaOriginal} del archivo.";
                    } else {
                        $combinacionesEnArchivo[$clave] = $index + 2;
                    }
                }

                if (!empty($errores)) {
                    return;
                }

                foreach ($rows as $index => $row) {

                    $filaTieneDatos = collect($row)->filter(fn($v) => !is_null($v) && trim((string)$v) !== '')->isNotEmpty();
                    if (!$filaTieneDatos) continue;

                    $filaNum = $index + 2;

                    // ── MAPEO DE COLUMNAS ─────────────────────────────
                    $ruc                   = trim((string)($row['A'] ?? ''));
                    $razonSocial           = trim((string)($row['B'] ?? ''));
                    $nombreComercial       = trim((string)($row['C'] ?? ''));
                    $sectorNombre          = strtoupper(trim((string)($row['D'] ?? '')));
                    $rubroNombre           = strtoupper(trim((string)($row['E'] ?? '')));
                    $actividadComercial    = trim((string)($row['F'] ?? ''));
                    $paisNombre            = strtoupper(trim((string)($row['G'] ?? '')));
                    $regionNombre          = strtoupper(trim((string)($row['H'] ?? '')));
                    $provinciaNombre       = strtoupper(trim((string)($row['I'] ?? '')));
                    $distritoNombre        = strtoupper(trim((string)($row['J'] ?? '')));
                    $direccion             = trim((string)($row['K'] ?? ''));
                    $tipoDocNombre         = strtoupper(trim((string)($row['L'] ?? '')));
                    $numeroDoc             = trim((string)($row['M'] ?? ''));
                    $apellidoPaterno       = strtoupper(trim((string)($row['N'] ?? '')));
                    $apellidoMaterno       = strtoupper(trim((string)($row['O'] ?? '')));
                    $nombres               = strtoupper(trim((string)($row['P'] ?? '')));
                    $generoNombre          = strtoupper(trim((string)($row['Q'] ?? '')));
                    $discapacidad          = strtoupper(trim((string)($row['R'] ?? '')));
                    $celular               = trim((string)($row['S'] ?? ''));
                    $correo                = strtolower(trim((string)($row['T'] ?? '')));
                    $personalAsesoria      = strtoupper(trim((string)($row['U'] ?? '')));
                    $personalFormalizacion = strtoupper(trim((string)($row['V'] ?? '')));

                    if (empty($numeroDoc)) {
                        $errores[] = "Fila {$filaNum}: número de documento vacío.";
                        continue;
                    }

                    // ── RESOLVER IDs desde catálogos ──────────────────
                    $sectorId    = $sectores[strtoupper($sectorNombre)]     ?? null;
                    $rubroId     = $rubros[strtoupper($rubroNombre)]         ?? null;
                    $paisId      = $paises[strtoupper($paisNombre)]          ?? null;
                    $regionId    = $ciudades[strtoupper($regionNombre)]      ?? null;
                    $provinciaId = $provincias[strtoupper($provinciaNombre)] ?? null;
                    $distritoId  = $distritos[strtoupper($distritoNombre)]   ?? null;
                    $tipoDocId   = $tiposDocs[strtoupper($tipoDocNombre)]    ?? null;
                    $generoId    = $generos[strtoupper($generoNombre)]       ?? null;

                    // ── UPSERT Empresario ─────────────────────────────
                    $empresario = Empresario::where('ruc', $ruc)
                        ->where('numero_dni', $numeroDoc)
                        ->first();

                    $datosEmpresario = [
                        'razon_social'               => $razonSocial        ?: null,
                        'nombre_comercial'           => $nombreComercial    ?: null,
                        'sector_economico_id'        => $sectorId,
                        'rubro_id'                   => $rubroId,
                        'actividad_comercial_nombre' => $actividadComercial ?: null,
                        'pais_id'                    => $paisId,
                        'region_id'                  => $regionId,
                        'provincia_id'               => $provinciaId,
                        'distrito_id'                => $distritoId,
                        'direccion'                  => $direccion          ?: null,
                        'tipo_documento_id'          => $tipoDocId,
                        'apellido_paterno'           => $apellidoPaterno    ?: null,
                        'apellido_materno'           => $apellidoMaterno    ?: null,
                        'nombres'                    => $nombres            ?: null,
                        'genero_id'                  => $generoId,
                        'discapacidad'               => $discapacidad === 'SI' ? 1 : 0,
                        'celular'                    => $celular            ?: null,
                        'correo_electronico'         => $correo             ?: null,
                    ];

                    if ($empresario) {
                        $empresario->update($datosEmpresario);
                        $actualizados++;
                    } else {
                        $empresario = Empresario::create(array_merge($datosEmpresario, [
                            'ruc'        => $ruc       ?: null,
                            'numero_dni' => $numeroDoc,
                        ]));
                        $registrados++;
                    }

                    // ── UPSERT EmpresarioActividad ────────────────────
                    // ✅ Buscar por slug + numero_dni, actualizar empresario_id siempre
                    // ── UPSERT EmpresarioActividad ────────────────────
                    // ✅ Clave única: slug + empresario_id (no numero_dni)
                    EmpresarioActividad::updateOrCreate(
                        [
                            'slug'          => $slug,
                            'empresario_id' => $empresario->id,  // ✅ identifica unívocamente
                        ],
                        [
                            'actividad_id'           => $actividad->id,
                            'numero_dni'             => $numeroDoc,
                            'personal_asesoria'      => $personalAsesoria      === 'SI' ? 1 : 0,
                            'personal_formalizacion' => $personalFormalizacion  === 'SI' ? 1 : 0,
                        ]
                    );
                }

                $total = EmpresarioActividad::where('slug', $slug)->count();
                $actividad->update(['total_participantes' => $total]);
            });

            return response()->json([
                'status'       => 200,
                'message'      => 'Importación completada correctamente.',
                'registrados'  => $registrados,
                'actualizados' => $actualizados,
                'errores'      => $errores,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'Error al importar el archivo.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
