<?php

namespace App\Http\Controllers\Import;

use App\Http\Controllers\Controller;
use App\Models\ActividadPnte;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\District;
use App\Models\EconomicSector;
use App\Models\Empresario;
use App\Models\EmpresarioActividad;
use App\Models\Gender;
use App\Models\PropagandaMedia;
use App\Models\Province;
use App\Models\RoleCompany;
use App\Models\SedQuestion;
use App\Models\Typedocument;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class SedInscritosController extends Controller
{
    private const SECTOR_ALIASES = [
        'SERVICIO' => 'SERVICIOS',
    ];

    private const RUBRO_ALIASES = [
        'ALIMENTOS Y BEBIDADS' => 'ALIMENTOS Y BEBIDAS',
        'DECO HOGAR' => 'DECO Y HOGAR',
    ];

    private const CARGO_ALIASES = [
        'ADMINISTRATIVO' => 'GERENTE ADMINISTRATIVO',
        'ADMINISTRADOR' => 'GERENTE ADMINISTRATIVO',
        'ADMINISTRADORA' => 'GERENTE ADMINISTRATIVO',
        'DIRECTOR' => 'GERENTE GENERAL',
        'DIRECTORA' => 'GERENTE GENERAL',
        'DUENO' => 'PROPIETARIO A',
        'DUENA' => 'PROPIETARIO A',
        'PROPIETARIO' => 'PROPIETARIO A',
        'PROPIETARIA' => 'PROPIETARIO A',
        'REPRESENTANTE' => 'GERENTE GENERAL',
        'REPRESENTANTE LEGAL' => 'GERENTE GENERAL',
    ];

    public function importarInscritosSed(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $actividad = ActividadPnte::where('slug', $slug)->firstOrFail();

        try {
            ini_set('memory_limit', '512M');
            set_time_limit(300);

            $spreadsheet = IOFactory::load($request->file('file')->getPathname());
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, false, true);
            array_shift($rows);

            $catalogos = $this->catalogos();

            $validas = [];
            $errores = [];
            $vistos = [];
            $dnisEnExcel = [];

            foreach ($rows as $index => $row) {
                $fila = $index + 2;

                if (! $this->filaConDatos($row)) {
                    continue;
                }

                $dniFila = $this->soloDigitos($row['L'] ?? null);

                if ($dniFila !== '') {
                    $tipoDocumentoFila = $this->normalizeKey($row['K'] ?? null);

                    if ($tipoDocumentoFila === 'DNI' && strlen($dniFila) === 7) {
                        $dniFila = str_pad($dniFila, 8, '0', STR_PAD_LEFT);
                    }

                    $dnisEnExcel[$dniFila] = $dniFila;
                }

                $data = $this->mapearFila($row, $catalogos, $fila, $errores);

                if ($data === null) {
                    continue;
                }

                $dnisEnExcel[$data['numero_dni']] = $data['numero_dni'];

                $clave = ($data['ruc'] ?: 'SIN_RUC').'|'.$data['numero_dni'];

                if (isset($vistos[$clave])) {
                    $this->agregarError(
                        $errores,
                        $fila,
                        'A',
                        'RUC + Documento',
                        ($data['ruc'] ?: 'SIN RUC').' - '.$data['numero_dni'],
                        'RUC + Documento repetido con fila '.$vistos[$clave].'; se omite esta fila'
                    );

                    continue;
                }

                $vistos[$clave] = $fila;
                $validas[] = $data;
            }

            $registrados = 0;
            $actualizados = 0;

            DB::transaction(function () use ($validas, $actividad, $slug, $dnisEnExcel, &$registrados, &$actualizados) {
                $fechaActual = now()->format('d/m/Y H:i');

                foreach ($validas as $data) {
                    $empresario = Empresario::query()
                        ->when(
                            $data['ruc'] !== '',
                            fn ($query) => $query->where('ruc', $data['ruc']),
                            fn ($query) => $query->whereNull('ruc')
                        )
                        ->where('numero_dni', $data['numero_dni'])
                        ->first();

                    if (! $empresario && $data['ruc'] !== '') {
                        $empresario = Empresario::query()
                            ->where('numero_dni', $data['numero_dni'])
                            ->where(fn ($query) => $query->whereNull('ruc')->orWhere('ruc', ''))
                            ->first();
                    }

                    $atributos = [
                        'ruc' => $data['ruc'] !== '' ? $data['ruc'] : null,
                        'razon_social' => $data['razon_social'],
                        'nombre_comercial' => $data['nombre_comercial'],
                        'sector_economico_id' => $data['sector_economico_id'],
                        'rubro_id' => $data['rubro_id'],
                        'actividad_comercial_nombre' => $data['actividad_comercial'],
                        'pais_id' => $data['pais_id'],
                        'region_id' => $data['region_id'],
                        'provincia_id' => $data['provincia_id'],
                        'distrito_id' => $data['distrito_id'],
                        'direccion' => $data['direccion'],
                        'tipo_documento_id' => $data['tipo_documento_id'],
                        'apellido_paterno' => $data['apellido_paterno'],
                        'apellido_materno' => $data['apellido_materno'],
                        'nombres' => $data['nombres'],
                        'genero_id' => $data['genero_id'],
                        'discapacidad' => $data['discapacidad'],
                        'celular' => $data['celular'],
                        'correo_electronico' => $data['correo_electronico'],
                        'cargo_empresa_id' => $data['cargo_empresa_id'],
                        'fecha_nacimiento' => $data['fecha_nacimiento'],
                        'edad' => $data['edad'],
                    ];

                    if ($empresario) {
                        $empresario->update($atributos);
                        $actualizados++;
                    } else {
                        $empresario = Empresario::create(array_merge($atributos, [
                            'numero_dni' => $data['numero_dni'],
                        ]));
                        $registrados++;
                    }

                    $empresarioActividad = EmpresarioActividad::firstOrNew([
                        'slug' => $slug,
                        'numero_dni' => $data['numero_dni'],
                    ]);

                    $empresarioActividad->actividad_id = $actividad->id;
                    $empresarioActividad->empresario_id = $empresario->id;

                    if (blank($empresarioActividad->fecha_asistencia)) {
                        $empresarioActividad->fecha_asistencia = $fechaActual;
                    }

                    $empresarioActividad->save();

                    SedQuestion::updateOrCreate(
                        [
                            'slug' => $slug,
                            'documentnumber' => $data['numero_dni'],
                        ],
                        [
                            'question_1' => $data['question_1'],
                            'question_2' => $data['question_2'],
                            'question_3' => $data['question_3'],
                            'question_4' => $data['question_4'],
                            'question_5' => $data['question_5'],
                            'propagandamedia_id' => $data['propagandamedia_id'],
                            'tipo_asistente' => $data['tipo_asistente'],
                        ]
                    );
                }

                if ($validas !== [] && $dnisEnExcel !== []) {
                    $noIncluidos = EmpresarioActividad::query()
                        ->where('slug', $slug)
                        ->whereNotNull('fecha_asistencia')
                        ->where('fecha_asistencia', '<>', '')
                        ->whereNotIn('numero_dni', array_values($dnisEnExcel))
                        ->get();

                    foreach ($noIncluidos as $registro) {
                        $registro->update([
                            'horario_inicio' => $this->fechaCortaAsistencia($registro->fecha_asistencia),
                            'fecha_asistencia' => null,
                        ]);
                    }
                }

                $actividad->update([
                    'total_participantes' => EmpresarioActividad::where('slug', $slug)->count(),
                ]);
            });

            return response()->json([
                'status' => 200,
                'message' => 'Importación completada correctamente.',
                'registrados' => $registrados,
                'actualizados' => $actualizados,
                'errores' => $errores,
                'html_errores' => $this->htmlErrores($errores),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al importar el archivo.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function catalogos(): array
    {
        $mapa = fn ($items) => $items
            ->mapWithKeys(fn ($item) => [$this->normalizeKey($item->name) => $item->id])
            ->all();

        $provincias = Province::query()
            ->orderBy('id')
            ->get(['id', 'name', 'city_id']);

        $distritos = District::query()->get(['id', 'name', 'province_id']);

        return [
            'sectores' => $mapa(EconomicSector::query()->get(['id', 'name'])),
            'rubros' => $mapa(Category::query()->get(['id', 'name'])),
            'ciudades' => $mapa(City::query()->get(['id', 'name'])),
            'provincias' => $provincias
                ->mapWithKeys(fn ($provincia) => [
                    $this->normalizeKey($provincia->name).'|'.$provincia->city_id => $provincia->id,
                ])
                ->all(),
            'provinciasNombre' => $provincias
                ->groupBy(fn ($provincia) => $this->normalizeKey($provincia->name))
                ->map(fn ($grupo) => $grupo->first()->id)
                ->all(),
            'distritos' => $distritos
                ->mapWithKeys(fn ($distrito) => [
                    $this->normalizeKey($distrito->name).'|'.$distrito->province_id => $distrito->id,
                ])
                ->all(),
            'tiposDocumento' => $mapa(Typedocument::query()->get(['id', 'name'])),
            'generos' => $mapa(Gender::query()->get(['id', 'name'])),
            'cargos' => $mapa(RoleCompany::query()->get(['id', 'name'])),
            'medios' => $mapa(PropagandaMedia::query()->get(['id', 'name'])),
            'paisPeru' => Country::query()->whereRaw('UPPER(name) = ?', ['PERU'])->value('id'),
        ];
    }

    private function mapearFila(array $row, array $catalogos, int $fila, array &$errores): ?array
    {
        $erroresAntes = count($errores);

        $ruc = $this->soloDigitos($row['A'] ?? null);
        $razonSocial = $this->textoONull($row['B'] ?? null);
        $nombreComercial = $this->textoONull($row['C'] ?? null);
        $sectorNombre = $this->normalizeKey($row['D'] ?? null);
        $rubroNombre = $this->normalizeKey($row['E'] ?? null);
        $actividadComercial = $this->textoONull($row['F'] ?? null);
        $regionNombre = $this->normalizeKey($row['G'] ?? null);
        $provinciaNombre = $this->normalizeKey($row['H'] ?? null);
        $distritoNombre = $this->normalizeKey($row['I'] ?? null);
        $direccion = $this->textoONull($row['J'] ?? null);
        $tipoDocumentoNombre = $this->normalizeKey($row['K'] ?? null);
        $numeroDni = $this->soloDigitos($row['L'] ?? null);
        $nombres = $this->nombrePropio($row['M'] ?? null);
        $apellidoPaterno = $this->nombrePropio($row['N'] ?? null);
        $apellidoMaterno = $this->nombrePropio($row['O'] ?? null);
        $generoNombre = $this->normalizeKey($row['P'] ?? null);
        $discapacidadNombre = $this->normalizeKey($row['Q'] ?? null);
        $celular = $this->soloDigitos($row['R'] ?? null);
        $correo = $this->textoONull($row['S'] ?? null);
        $fechaNacimiento = $this->parseFecha($row['T'] ?? null);
        $edadExcel = $this->soloDigitos($row['U'] ?? null);
        $cargoNombre = $this->normalizeKey($row['V'] ?? null);
        $medioNombre = $this->normalizeKey($row['W'] ?? null);
        $tipoAsistenteNombre = $this->normalizeKey($row['X'] ?? null);

        $question1 = $this->textoONull($row['Y'] ?? null);
        $question2 = $this->textoONull($row['Z'] ?? null);
        $question3 = $this->textoONull($row['AA'] ?? null);
        $question4 = $this->textoONull($row['AB'] ?? null);
        $question5 = $this->textoONull($row['AC'] ?? null);

        if ($ruc !== '' && strlen($ruc) !== 11) {
            $this->agregarError($errores, $fila, 'A', 'RUC', $ruc, 'El RUC debe tener 11 dígitos');
        }

        if ($tipoDocumentoNombre === '') {
            $this->agregarError($errores, $fila, 'K', 'Tipo de documento', '', 'Tipo de documento vacío');
        }

        if ($numeroDni === '') {
            $this->agregarError($errores, $fila, 'L', 'Número de documento', '', 'Número de documento vacío');
        } else {
            if ($tipoDocumentoNombre === 'DNI' && strlen($numeroDni) === 7) {
                $numeroDni = str_pad($numeroDni, 8, '0', STR_PAD_LEFT);
            }

            if (strlen($numeroDni) < 8 || strlen($numeroDni) > 12) {
                $this->agregarError(
                    $errores,
                    $fila,
                    'L',
                    'Número de documento',
                    $numeroDni,
                    'El número de documento debe tener entre 8 y 12 dígitos'
                );
            }
        }

        if ($nombres === null) {
            $this->agregarError($errores, $fila, 'M', 'Nombres', '', 'Completar los nombres');
        }

        if ($apellidoPaterno === null) {
            $this->agregarError($errores, $fila, 'N', 'Apellido paterno', '', 'Completar el apellido paterno');
        }

        if ($fechaNacimiento === null) {
            $this->agregarError(
                $errores,
                $fila,
                'T',
                'Fecha de nacimiento',
                $this->cleanText($row['T'] ?? null),
                'Fecha de nacimiento vacía o inválida'
            );
        }

        $sectorKey = $this->aplicarAlias($sectorNombre, self::SECTOR_ALIASES);
        $sectorId = $catalogos['sectores'][$sectorKey] ?? null;

        if ($sectorKey !== '' && $sectorId === null) {
            $this->agregarError($errores, $fila, 'D', 'Sector económico', $sectorNombre, 'Sector económico no encontrado');
        }

        $rubroKey = $this->aplicarAlias($rubroNombre, self::RUBRO_ALIASES);
        $rubroId = $catalogos['rubros'][$rubroKey] ?? null;

        if ($rubroKey !== '' && $rubroId === null) {
            $this->agregarError($errores, $fila, 'E', 'Rubro', $rubroNombre, 'Rubro no encontrado');
        }

        $regionId = $catalogos['ciudades'][$regionNombre] ?? null;

        if ($regionNombre !== '' && $regionId === null) {
            $this->agregarError($errores, $fila, 'G', 'Región', $regionNombre, 'Región no encontrada');
        }

        $provinciaId = null;

        if ($provinciaNombre !== '') {
            if ($regionId !== null) {
                $provinciaId = $catalogos['provincias'][$provinciaNombre.'|'.$regionId] ?? null;
            }

            if ($provinciaId === null) {
                $provinciaId = $catalogos['provinciasNombre'][$provinciaNombre] ?? null;
            }

            if ($provinciaId === null) {
                $this->agregarError($errores, $fila, 'H', 'Provincia', $provinciaNombre, 'Provincia no encontrada');
            }
        }

        $distritoId = null;

        if ($distritoNombre !== '') {
            if ($provinciaId !== null) {
                $distritoId = $catalogos['distritos'][$distritoNombre.'|'.$provinciaId] ?? null;
            }

            if ($distritoId === null) {
                $this->agregarError($errores, $fila, 'I', 'Distrito', $distritoNombre, 'Distrito no encontrado');
            }
        }

        $tipoDocumentoId = $catalogos['tiposDocumento'][$tipoDocumentoNombre] ?? null;

        if ($tipoDocumentoNombre !== '' && $tipoDocumentoId === null) {
            $this->agregarError(
                $errores,
                $fila,
                'K',
                'Tipo de documento',
                $tipoDocumentoNombre,
                'Tipo de documento no encontrado'
            );
        }

        if (count($errores) > $erroresAntes) {
            return null;
        }

        $generoId = $catalogos['generos'][$generoNombre] ?? null;
        $cargoId = $this->resolverAlias($cargoNombre, $catalogos['cargos'], self::CARGO_ALIASES);
        $medioId = $catalogos['medios'][$medioNombre] ?? null;

        $edad = $fechaNacimiento !== null
            ? (string) Carbon::parse($fechaNacimiento)->age
            : (strlen($edadExcel) > 0 ? substr($edadExcel, 0, 3) : null);

        return [
            'ruc' => $ruc,
            'razon_social' => $razonSocial,
            'nombre_comercial' => $nombreComercial,
            'sector_economico_id' => $sectorId,
            'rubro_id' => $rubroId,
            'actividad_comercial' => $actividadComercial,
            'pais_id' => $catalogos['paisPeru'],
            'region_id' => $regionId,
            'provincia_id' => $provinciaId,
            'distrito_id' => $distritoId,
            'direccion' => $direccion,
            'tipo_documento_id' => $tipoDocumentoId,
            'numero_dni' => $numeroDni,
            'apellido_paterno' => $apellidoPaterno,
            'apellido_materno' => $apellidoMaterno,
            'nombres' => $nombres,
            'genero_id' => $generoId,
            'discapacidad' => in_array($discapacidadNombre, ['SI', 'S', '1', 'TRUE', 'X'], true) ? 1 : 0,
            'celular' => strlen($celular) === 9 ? $celular : null,
            'correo_electronico' => $correo !== null ? Str::lower($correo) : null,
            'cargo_empresa_id' => $cargoId,
            'fecha_nacimiento' => $fechaNacimiento,
            'edad' => $edad,
            'propagandamedia_id' => $medioId,
            'tipo_asistente' => $tipoAsistenteNombre === 'REPRESENTANTE' ? 1 : 0,
            'question_1' => $question1,
            'question_2' => $question2,
            'question_3' => $question3,
            'question_4' => $question4,
            'question_5' => $question5,
        ];
    }

    private function filaConDatos(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && $this->cleanText($value) !== '') {
                return true;
            }
        }

        return false;
    }

    private function soloDigitos($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_int($value) || is_float($value)) {
            $value = sprintf('%.0f', $value);
        }

        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    private function textoONull($value): ?string
    {
        $texto = $this->cleanText($value);

        return $texto === '' ? null : $texto;
    }

    private function nombrePropio($value): ?string
    {
        $texto = $this->textoONull($value);

        return $texto !== null ? Str::upper($texto) : null;
    }

    private function cleanText($value): string
    {
        if ($value === null) {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        $texto = (string) $value;
        $texto = str_replace(["\u{00A0}", "\u{2007}", "\u{202F}", "\t", "\n", "\r"], ' ', $texto);
        $texto = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $texto) ?? $texto;
        $texto = preg_replace('/ {2,}/u', ' ', $texto) ?? $texto;

        return trim($texto);
    }

    private function normalizeKey($value): string
    {
        $texto = $this->cleanText($value);

        if ($texto === '') {
            return '';
        }

        $texto = Str::upper(Str::ascii($texto));
        $texto = str_replace(
            ['(', ')', '[', ']', '{', '}', '.', ',', ';', ':', '/', '\\', '-', '_', '"', "'"],
            ' ',
            $texto
        );
        $texto = preg_replace('/ {2,}/u', ' ', $texto) ?? $texto;

        return trim($texto);
    }

    private function aplicarAlias(string $key, array $aliases): string
    {
        return $aliases[$key] ?? $key;
    }

    private function resolverAlias(string $key, array $mapa, array $aliases): ?int
    {
        if ($key === '') {
            return null;
        }

        if (isset($mapa[$key])) {
            return $mapa[$key];
        }

        $alias = $aliases[$key] ?? null;

        return $alias !== null ? ($mapa[$alias] ?? null) : null;
    }

    private function fechaCortaAsistencia($value): ?string
    {
        $texto = trim((string) $value);

        if ($texto === '') {
            return null;
        }

        if (preg_match('/^(\d{4})[\/-](\d{1,2})[\/-](\d{1,2})/u', $texto, $partes)) {
            return sprintf('%02d/%02d/%04d', (int) $partes[3], (int) $partes[2], (int) $partes[1]);
        }

        if (preg_match('/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{2,4})/u', $texto, $partes)) {
            $anio = (int) $partes[3];

            if ($anio < 100) {
                $anio += $anio > 30 ? 1900 : 2000;
            }

            return sprintf('%02d/%02d/%04d', (int) $partes[1], (int) $partes[2], $anio);
        }

        return substr($texto, 0, 10);
    }

    private function parseFecha($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            $serial = (float) $value;

            if ($serial > 0 && $serial < 100000) {
                try {
                    $fecha = ExcelDate::excelToDateTimeObject($serial);

                    if ((int) $fecha->format('Y') >= 1900) {
                        return $fecha->format('Y-m-d');
                    }
                } catch (Throwable) {
                }
            }
        }

        $texto = $this->cleanText($value);

        if ($texto === '') {
            return null;
        }

        $texto = str_replace(['.', ' '], '/', $texto);
        $texto = preg_replace('/[^0-9\/\-]/', '', $texto) ?? '';

        if (preg_match('/^(\d{1,2})[\/\-](\d{2})(\d{3})$/', $texto, $partes)) {
            $texto = $partes[1].'/'.$partes[2].'/1'.$partes[3];
        }

        foreach (['d/m/Y', 'd-m-Y', 'd/m/y', 'd-m-y', 'Y-m-d', 'Y/m/d'] as $formato) {
            try {
                $fecha = Carbon::createFromFormat('!'.$formato, $texto);
                $anio = (int) $fecha->format('Y');

                if ($anio >= 1900 && $anio <= (int) now()->format('Y')) {
                    return $fecha->format('Y-m-d');
                }
            } catch (Throwable) {
            }
        }

        return null;
    }

    private function agregarError(
        array &$errores,
        int $fila,
        string $columna,
        string $campo,
        $valor,
        string $mensaje
    ): void {
        $errores[] = [
            'fila' => $fila,
            'celda' => $columna.$fila,
            'campo' => $campo,
            'valor' => $valor,
            'error' => $mensaje,
        ];
    }

    private function htmlErrores(array $errores): string
    {
        if ($errores === []) {
            return '';
        }

        $html = '<div style="max-height:500px;overflow:auto"><table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse:collapse;font-family:Arial;font-size:13px"><thead style="background:#f5f5f5"><tr><th>Fila</th><th>Celda</th><th>Campo</th><th>Valor</th><th>Error</th></tr></thead><tbody>';

        foreach ($errores as $error) {
            $html .= '<tr><td>'.e($error['fila']).'</td><td>'.e($error['celda']).'</td><td>'.e($error['campo']).'</td><td>'.e($error['valor']).'</td><td style="color:red">'.e($error['error']).'</td></tr>';
        }

        return $html.'</tbody></table></div>';
    }
}
