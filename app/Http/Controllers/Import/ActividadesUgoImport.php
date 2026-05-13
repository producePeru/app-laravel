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

                $sectores = EconomicSector::pluck('id', 'name')
                    ->mapWithKeys(fn($id, $name) => [strtoupper($name) => $id]);

                $rubros = Category::pluck('id', 'name')
                    ->mapWithKeys(fn($id, $name) => [strtoupper($name) => $id]);

                $paises = Country::pluck('id', 'name')
                    ->mapWithKeys(fn($id, $name) => [strtoupper($name) => $id]);

                $ciudades = City::pluck('id', 'name')
                    ->mapWithKeys(fn($id, $name) => [strtoupper($name) => $id]);

                $provincias = Province::pluck('id', 'name')
                    ->mapWithKeys(fn($id, $name) => [strtoupper($name) => $id]);

                $distritos = District::pluck('id', 'name')
                    ->mapWithKeys(fn($id, $name) => [strtoupper($name) => $id]);

                $tiposDocs = Typedocument::pluck('id', 'name')
                    ->mapWithKeys(fn($id, $name) => [strtoupper($name) => $id]);

                $generos = Gender::pluck('id', 'name')
                    ->mapWithKeys(fn($id, $name) => [strtoupper($name) => $id]);

                // ✅ VALIDAR DUPLICADOS EN EL MISMO ARCHIVO
                $combinacionesEnArchivo = [];

                foreach ($rows as $index => $row) {

                    $filaTieneDatos = collect($row)
                        ->filter(fn($v) => !is_null($v) && trim((string)$v) !== '')
                        ->isNotEmpty();

                    if (!$filaTieneDatos) {
                        continue;
                    }

                    $filaNum = $index + 2;

                    $ruc       = trim((string)($row['A'] ?? ''));
                    $numeroDoc = trim((string)($row['M'] ?? ''));

                    if (empty($numeroDoc)) {

                        $errores[] = [
                            'fila'   => $filaNum,
                            'celda'  => 'M' . $filaNum,
                            'campo'  => 'Número documento',
                            'valor'  => '',
                            'error'  => 'Número de documento vacío'
                        ];

                        continue;
                    }

                    // ✅ SI RUC ES NULL SOLO USA DOCUMENTO
                    $clave = ($ruc ?: 'SIN_RUC') . '|' . $numeroDoc;

                    if (isset($combinacionesEnArchivo[$clave])) {

                        $filaOriginal = $combinacionesEnArchivo[$clave];

                        $errores[] = [
                            'fila'   => $filaNum,
                            'celda'  => 'A' . $filaNum,
                            'campo'  => 'RUC + Documento',
                            'valor'  => $ruc . ' - ' . $numeroDoc,
                            'error'  => "Duplicado con fila {$filaOriginal}"
                        ];
                    } else {

                        $combinacionesEnArchivo[$clave] = $filaNum;
                    }
                }

                if (!empty($errores)) {
                    return;
                }

                foreach ($rows as $index => $row) {

                    $filaTieneDatos = collect($row)
                        ->filter(fn($v) => !is_null($v) && trim((string)$v) !== '')
                        ->isNotEmpty();

                    if (!$filaTieneDatos) {
                        continue;
                    }

                    $filaNum = $index + 2;

                    // ─────────────────────────────────────
                    // MAPEO COLUMNAS
                    // ─────────────────────────────────────
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

                    // ✅ SOLO DOCUMENTO OBLIGATORIO
                    if (empty($numeroDoc)) {

                        $errores[] = [
                            'fila'   => $filaNum,
                            'celda'  => 'M' . $filaNum,
                            'campo'  => 'Número documento',
                            'valor'  => '',
                            'error'  => 'Número de documento vacío'
                        ];

                        continue;
                    }

                    // ─────────────────────────────────────
                    // RESOLVER IDS
                    // ─────────────────────────────────────
                    $sectorId    = $sectores[$sectorNombre]     ?? null;
                    $rubroId     = $rubros[$rubroNombre]        ?? null;
                    $paisId      = $paises[$paisNombre]         ?? null;
                    $regionId    = $ciudades[$regionNombre]     ?? null;
                    $provinciaId = $provincias[$provinciaNombre] ?? null;
                    $distritoId  = $distritos[$distritoNombre]  ?? null;
                    $tipoDocId   = $tiposDocs[$tipoDocNombre]   ?? null;
                    $generoId    = $generos[$generoNombre]      ?? null;

                    // ✅ RUC Y RAZON SOCIAL PUEDEN SER NULL
                    // NO ALTERAR LOGICA EXISTENTE

                    // ─────────────────────────────────────
                    // BUSCAR EMPRESARIO
                    // ─────────────────────────────────────
                    $query = Empresario::query();

                    if (!empty($ruc)) {
                        $query->where('ruc', $ruc);
                    } else {
                        $query->whereNull('ruc');
                    }

                    $empresario = $query
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

                        $empresario = Empresario::create(array_merge(
                            $datosEmpresario,
                            [
                                'ruc'        => $ruc ?: null,
                                'numero_dni' => $numeroDoc,
                            ]
                        ));

                        $registrados++;
                    }

                    // ─────────────────────────────────────
                    // EMPRESARIO ACTIVIDAD
                    // ─────────────────────────────────────
                    EmpresarioActividad::updateOrCreate(
                        [
                            'slug'          => $slug,
                            'empresario_id' => $empresario->id,
                        ],
                        [
                            'actividad_id'           => $actividad->id,
                            'numero_dni'             => $numeroDoc,
                            'personal_asesoria'      => $personalAsesoria === 'SI' ? 1 : 0,
                            'personal_formalizacion' => $personalFormalizacion === 'SI' ? 1 : 0,
                        ]
                    );
                }

                $total = EmpresarioActividad::where('slug', $slug)->count();

                $actividad->update([
                    'total_participantes' => $total
                ]);
            });

            // ✅ HTML ERRORES
            $htmlErrores = '';

            if (!empty($errores)) {

                $htmlErrores .= '
            <div style="max-height:500px;overflow:auto">
                <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse:collapse;font-family:Arial;font-size:13px">
                    <thead style="background:#f5f5f5">
                        <tr>
                            <th>Fila</th>
                            <th>Celda</th>
                            <th>Campo</th>
                            <th>Valor</th>
                            <th>Error</th>
                        </tr>
                    </thead>
                    <tbody>
            ';

                foreach ($errores as $error) {

                    $htmlErrores .= '
                    <tr>
                        <td>' . $error['fila'] . '</td>
                        <td>' . $error['celda'] . '</td>
                        <td>' . $error['campo'] . '</td>
                        <td>' . $error['valor'] . '</td>
                        <td style="color:red">' . $error['error'] . '</td>
                    </tr>
                ';
                }

                $htmlErrores .= '
                    </tbody>
                </table>
            </div>
            ';
            }

            return response()->json([
                'status'       => 200,
                'message'      => 'Importación completada correctamente.',
                'registrados'  => $registrados,
                'actualizados' => $actualizados,
                'errores'      => $errores,
                'html_errores' => $htmlErrores,
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
