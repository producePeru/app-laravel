<?php

/**
 * INSTRUCCIONES DE USO
 * --------------------
 * 1. Coloca este archivo en app/Exports/ActividadReporteExport.php
 * 2. Instala la dependencia: composer require maatwebsite/excel
 * 3. En tu Controller:
 *
 *      use App\Exports\ActividadReporteExport;
 *      use Maatwebsite\Excel\Facades\Excel;
 *
 *      public function descargarExcel(Request $request, $slug)
 *      {
 *          return Excel::download(
 *              new ActividadReporteExport($slug),
 *              'reporte_actividad_' . $slug . '_' . now()->format('Ymd_His') . '.xlsx'
 *          );
 *      }
 *
 * RENDIMIENTO
 * -----------
 * - Todo lo que puede precargarse se carga UNA SOLA VEZ en el constructor:
 *     · La ActividadPnte del slug
 *     · Todas las SedQuestion del slug  (indexadas por documentnumber)
 *     · Todos los sedQuestionAnswer del slug (indexados por dni → [question => answer])
 *     · Todos los PropagandaMedia (lookup simple)
 * - El map() solo hace lookups en memoria ($this->xxxCache[key]), CERO queries extra.
 * - WithChunkReading: la query principal se ejecuta en bloques de 500 filas.
 * - ShouldQueue: generación en background (quitar si no usas colas).
 */

namespace App\Exports;

use App\Models\ActividadPnte;
use App\Models\SedQuestion;
use App\Models\sedQuestionAnswer;
use App\Models\PropagandaMedia;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;

class ActividadReporteExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithColumnWidths,
    WithChunkReading,
    ShouldQueue
{
    use Exportable;

    // ── Colores ──────────────────────────────────────────────────────────────
    const COLOR_INFO      = '1F4E79';
    const COLOR_EMPRESA   = 'C55A11';
    const COLOR_SED_FIXED = '375623';
    const COLOR_SED_DYN   = '7030A0';
    const COLOR_TEXT      = 'FFFFFF';

    protected string $slug;
    protected int    $rowIndex = 1;

    // ── Caches precargados en el constructor ─────────────────────────────────
    protected ActividadPnte $actividad;
    protected string        $fechasStr        = '';
    protected array         $dynamicQuestions = [];  // ['¿Pregunta X?' , ...]  en orden
    protected array         $sedCache         = [];  // [documentnumber => SedQuestion]
    protected array         $dynAnswersCache  = [];  // [documentnumber => [question => answer]]
    protected array         $mediaCache       = [];  // [id => name]

    // ── Opciones de respuesta para preguntas SED fijas ───────────────────────
    protected array $questionOptions = [
        'question_1' => [
            'sin_interes'         => 'A. No tengo interés en la tecnología; mi negocio depende solo de mi presencia física y el boca a boca.',
            'redes_sociales'      => 'B. Uso Facebook o WhatsApp porque otros lo hacen, pero no tengo un plan ni metas de ventas digitales.',
            'estrategia_digital'  => 'C. Tengo una estrategia digital clara y uso datos de mis ventas pasadas para decidir qué comprar o vender.',
            'plan_transformacion' => 'D. Tengo un Plan de Transformación Digital escrito y mi modelo de negocio se adapta rápidamente a los cambios del mercado tecnológico.',
        ],
        'question_2' => [
            'sin_interes'     => 'A. Solo yo tomo las decisiones y no usamos herramientas digitales para coordinar el trabajo.',
            'redes_sociales'  => 'B. Mis empleados usan sus WhatsApp personales para atender clientes, pero no han recibido capacitación en herramientas de gestión.',
            'capacitacion'    => 'C. Capacito a mi equipo en el uso de herramientas digitales y todos usamos un sistema común para registrar pedidos y tareas.',
            'lideres_digitales' => 'D. Contamos con líderes digitales en el equipo, todos tienen altas competencias digitales y tomamos decisiones basadas en reportes de datos en tiempo real.',
        ],
        'question_3' => [
            'celular'               => 'A. Solo tengo un celular básico para llamadas y no confío en los pagos digitales ni en internet.',
            'internet_basico'       => 'B. Tengo internet básico y uso computadoras personales para tareas simples (Word/Excel básico) sin protocolos de seguridad.',
            'internet_alta_velocidad' => 'C. Tengo internet de alta velocidad, uso software con licencia y protejo mi información con contraseñas y respaldos frecuentes.',
            'nube'                  => 'D. Uso servicios en la nube (Cloud), mi infraestructura está integrada y tengo sistemas de ciberseguridad para proteger los datos de mis clientes.',
        ],
        'question_4' => [
            'anotado'   => 'A. Todo lo anoto en cuadernos o lo tengo en la memoria; a veces pierdo el control de lo que falta.',
            'excel'     => 'B. Registro mis ventas en Excel al final del día, pero mi inventario y contabilidad los llevo por separado o en físico.',
            'software'  => 'C. Uso un software o App específica para controlar mi stock, mis ventas y emitir comprobantes electrónicos de forma automática.',
            'integrado' => 'D. Mi sistema está totalmente integrado: me avisa automáticamente cuando queda poco stock y genera reportes contables y de producción sin errores.',
        ],
        'question_5' => [
            'local'     => 'A. Solo me encuentran si pasan por mi local; no guardo datos de contacto de quienes me compran.',
            'excel'     => 'B. Respondo consultas por Facebook o WhatsApp, pero no tengo un catálogo digital ni analizo si los clientes están satisfechos.',
            'software'  => 'C. Tengo presencia en Google Maps, uso catálogos digitales y acepto múltiples pagos (Yape, Plin, POS). Mido la satisfacción de mis clientes.',
            'integrado' => 'D. Tengo una tienda online o CRM donde el cliente compra directamente y utilizo sus datos para enviarles ofertas personalizadas.',
        ],
    ];

    // ────────────────────────────────────────────────────────────────────────
    public function __construct(string $slug)
    {
        $this->slug = $slug;

        // 1) Actividad + relaciones geográficas (1 query)
        $this->actividad = ActividadPnte::with(['regionRel', 'provinciaRel', 'distritoRel'])
            ->where('slug', $slug)
            ->firstOrFail();

        $fechas = is_array($this->actividad->fechas)
            ? $this->actividad->fechas
            : json_decode($this->actividad->fechas ?? '[]', true);
        $this->fechasStr = implode(', ', array_map(
            fn($f) => Carbon::parse($f)->format('d/m/Y'),
            $fechas
        ));

        // 2) Todas las SedQuestion del slug (1 query) → indexadas por documentnumber
        SedQuestion::where('slug', $slug)
            ->get()
            ->each(function ($sq) {
                $this->sedCache[$sq->documentnumber] = $sq;
            });

        // 3) Todos los sedQuestionAnswer del slug (1 query)
        //    Estructura: [dni => [question => answer]]
        //    También construye la lista ordenada de preguntas dinámicas.
        $seenQuestions = [];
        sedQuestionAnswer::where('slug_sed', $slug)
            ->orderBy('order')
            ->get()
            ->each(function ($row) use (&$seenQuestions) {
                // índice principal por DNI
                $this->dynAnswersCache[$row->dni][$row->question] = $row->answer;

                // índice secundario por RUC (para cuando el DNI no coincide)
                if ($row->ruc) {
                    $this->dynAnswersCache['ruc:' . $row->ruc][$row->question] = $row->answer;
                }

                if (!isset($seenQuestions[$row->question])) {
                    $seenQuestions[$row->question] = true;
                    $this->dynamicQuestions[] = $row->question;
                }
            });

        // 4) PropagandaMedia (1 query)
        PropagandaMedia::all()->each(function ($m) {
            $this->mediaCache[$m->id] = $m->name;
        });
    }

    // ── Chunk size ───────────────────────────────────────────────────────────
    public function chunkSize(): int
    {
        return 500;
    }

    // ── Query principal ──────────────────────────────────────────────────────
    public function query()
    {
        return \App\Models\EmpresarioActividad::with([
            'empresario.sectorEconomico',
            'empresario.rubro',
            'empresario.actividadComercial',
            'empresario.region',
            'empresario.provincia',
            'empresario.distrito',
            'empresario.tipoDocumento',
            'empresario.genero',
            'empresario.cargoEmpresa',
        ])
            ->where('slug', $this->slug)
            ->orderBy('id');
    }

    // ── Cabeceras ─────────────────────────────────────────────────────────────
    public function headings(): array
    {
        $fixed = [
            '#',
            'EVENTO',
            'EVENTO REGIÓN',
            'EVENTO PROVINCIA',
            'EVENTO DISTRITO',
            'EVENTO LUGAR',
            'EVENTO FECHA(S)',
            'FECHA Y HORA DE ASISTENCIA',
            'RUC',
            'RAZÓN SOCIAL',
            'NOMBRE COMERCIAL',
            'SECTOR ECONÓMICO',
            'RUBRO',
            'ACTIVIDAD COMERCIAL',
            'REGIÓN',
            'PROVINCIA',
            'DISTRITO',
            'DIRECCIÓN',
            'TIPO ASISTENTE',
            'TIPO DOCUMENTO',
            'N° DOCUMENTO',
            'APELLIDO PATERNO',
            'APELLIDO MATERNO',
            'NOMBRES',
            'GÉNERO',
            '¿TIENE DISCAPACIDAD?',
            'CELULAR',
            'CORREO ELECTRÓNICO',
            'FECHA DE NACIMIENTO',
            'EDAD',
            'CARGO EN EMPRESA',
            '¿CÓMO SE ENTERÓ DEL EVENTO?',
            'FECHA Y HORA DE REGISTRO',
            // SED fijas
            '¿CÓMO PLANIFICAS EL CRECIMIENTO DE TU NEGOCIO USANDO TECNOLOGÍA? / ¿Pregunta 1?',
            '¿CÓMO SE INVOLUCRA TU EQUIPO EN EL USO DE HERRAMIENTAS DIGITALES? / ¿Pregunta 2?',
            '¿CON QUÉ HERRAMIENTAS TECNOLÓGICAS Y SEGURIDAD CUENTA TU NEGOCIO? / ¿Pregunta 3?',
            '¿CÓMO LLEVAS EL CONTROL DE INVENTARIOS, PRODUCCIÓN Y CONTABILIDAD? / ¿Pregunta 4?',
            '¿CÓMO TE ENCUENTRAN LOS CLIENTES NUEVOS? / ¿Pregunta 5?',
        ];

        return array_merge($fixed, $this->dynamicQuestions);
    }

    // ── Mapeo de cada fila (solo lookups en memoria, CERO queries) ───────────
    public function map($ea): array
    {
        $this->rowIndex++;
        $emp = $ea->empresario;

        // SedQuestion desde cache
        $sed = $this->sedCache[$emp?->numero_dni] ?? null;

        // Tipo asistente
        $tipoAsistente = match ((int)($sed?->tipo_asistente)) {
            1 => 'REPRESENTANTE',
            2 => 'INVITADO',
            default => '',
        };

        // Discapacidad
        $discapacidad = match ((int)($emp?->discapacidad)) {
            0 => 'NO',
            1 => 'SÍ',
            default => '',
        };

        // Preguntas SED fijas → resolver etiqueta desde options
        $sedFijas = [
            $this->resolveSedAnswer('question_1', $sed?->question_1),
            $this->resolveSedAnswer('question_2', $sed?->question_2),
            $this->resolveSedAnswer('question_3', $sed?->question_3),
            $this->resolveSedAnswer('question_4', $sed?->question_4),
            $this->resolveSedAnswer('question_5', $sed?->question_5),
        ];

        // Respuestas dinámicas desde cache
        // Primero intenta por DNI, luego por RUC como fallback
        $answerMap = $this->dynAnswersCache[$emp?->numero_dni]
            ?? $this->dynAnswersCache['ruc:' . ($emp?->ruc ?? '')]
            ?? [];

        $dynamicAnswers = array_map(
            fn($q) => $answerMap[$q] ?? '',
            $this->dynamicQuestions
        );

        // PropagandaMedia desde cache
        $propagandamediaName = $this->mediaCache[$sed?->propagandamedia_id ?? 0] ?? '';

        $row = [
            $this->rowIndex - 1,
            $this->actividad->tema ?? '',
            $this->actividad->regionRel?->name ?? '',
            $this->actividad->provinciaRel?->name ?? '',
            $this->actividad->distritoRel?->name ?? '',
            $this->actividad->lugar ?? '',
            $this->fechasStr,
            $ea->fecha_asistencia,
            $emp?->ruc ?? '',
            $emp?->razon_social ?? '',
            $emp?->nombre_comercial ?? '',
            $emp?->sectorEconomico?->name ?? '',
            $emp?->rubro?->name ?? '',
            $emp?->actividadComercial?->name ?? $emp?->actividad_comercial_nombre ?? '',
            $emp?->region?->name ?? '',
            $emp?->provincia?->name ?? '',
            $emp?->distrito?->name ?? '',
            $emp?->direccion ?? '',
            $tipoAsistente,
            $emp?->tipoDocumento?->name ?? '',
            $emp?->numero_dni ?? '',
            $emp?->apellido_paterno ?? '',
            $emp?->apellido_materno ?? '',
            $emp?->nombres ?? '',
            $emp?->genero?->name ?? '',
            $discapacidad,
            $emp?->celular ?? '',
            $emp?->correo_electronico ?? '',
            $emp?->fecha_nacimiento
                ? Carbon::parse($emp->fecha_nacimiento)->format('d/m/Y')
                : '',
            $emp?->edad ?? '',
            $emp?->cargoEmpresa?->name ?? '',
            $propagandamediaName,
            $ea->created_at
                ? Carbon::parse($ea->created_at)->format('d/m/Y h:i A')
                : '',
            // SED fijas
            $sedFijas[0],
            $sedFijas[1],
            $sedFijas[2],
            $sedFijas[3],
            $sedFijas[4],
        ];

        return array_merge($row, $dynamicAnswers);
    }

    // ── Estilos ───────────────────────────────────────────────────────────────
    public function styles(Worksheet $sheet)
    {
        $totalCols  = count($this->headings());
        $lastCol    = $this->colLetter($totalCols);
        $highestRow = $sheet->getHighestRow();

        $sheet->getRowDimension(1)->setRowHeight(50);

        // Rangos de color por grupo
        // Cols  1–8  : Info (azul)
        // Cols  9–33 : Empresa (naranja)
        // Cols 34–38 : SED fijas (verde)
        // Cols 39–N  : SED dinámicas (morado)
        $this->styleRange($sheet, 'A1',                    $this->colLetter(8)  . '1', self::COLOR_INFO);
        $this->styleRange($sheet, $this->colLetter(9) . '1', $this->colLetter(33) . '1', self::COLOR_EMPRESA);
        $this->styleRange($sheet, $this->colLetter(34) . '1', $this->colLetter(38) . '1', self::COLOR_SED_FIXED);

        if (!empty($this->dynamicQuestions)) {
            $dynEnd = 38 + count($this->dynamicQuestions);
            $this->styleRange($sheet, $this->colLetter(39) . '1', $this->colLetter($dynEnd) . '1', self::COLOR_SED_DYN);
        }

        // Body
        if ($highestRow > 1) {
            $bodyRange = "A2:{$lastCol}{$highestRow}";

            $sheet->getStyle($bodyRange)->getAlignment()
                ->setWrapText(true)
                ->setVertical(Alignment::VERTICAL_TOP);

            $sheet->getStyle($bodyRange)->getFont()
                ->setName('Arial')->setSize(10);

            $sheet->getStyle($bodyRange)->getBorders()
                ->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()->setARGB('FFD9D9D9');

            for ($r = 2; $r <= $highestRow; $r++) {
                $color = ($r % 2 === 0) ? 'FFF2F2F2' : 'FFFFFFFF';
                $sheet->getStyle("A{$r}:{$lastCol}{$r}")
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($color);
                $sheet->getRowDimension($r)->setRowHeight(28);
            }
        }

        $sheet->freezePane('A2');

        return [];
    }

    // ── Anchos de columna ─────────────────────────────────────────────────────
    public function columnWidths(): array
    {
        $widths = [
            'A' => 5,
            'B' => 30,
            'C' => 18,
            'D' => 18,
            'E' => 18,
            'F' => 25,
            'G' => 22,
            'H' => 22,
            'I' => 14,
            'J' => 30,
            'K' => 25,
            'L' => 20,
            'M' => 20,
            'N' => 25,
            'O' => 18,
            'P' => 18,
            'Q' => 18,
            'R' => 30,
            'S' => 16,
            'T' => 16,
            'U' => 14,
            'V' => 20,
            'W' => 20,
            'X' => 20,
            'Y' => 12,
            'Z' => 18,
            'AA' => 14,
            'AB' => 28,
            'AC' => 18,
            'AD' => 8,
            'AE' => 20,
            'AF' => 28,
            'AG' => 22,
            // SED fijas
            'AH' => 80,
            'AI' => 80,
            'AJ' => 80,
            'AK' => 80,
            'AL' => 80,
        ];

        // SED dinámicas
        foreach ($this->dynamicQuestions as $i => $_) {
            $col = $this->colLetter(39 + $i);
            $widths[$col] = 38;
        }

        return $widths;
    }

    // ── Helpers privados ──────────────────────────────────────────────────────

    /**
     * Resuelve la etiqueta completa de una respuesta SED fija.
     * Si la key pertenece al mapa de opciones la expande; si no, devuelve el valor crudo
     * (para el caso ['no','si','personal'] que son literales directos).
     */
    private function resolveSedAnswer(string $questionKey, ?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        return $this->questionOptions[$questionKey][$value] ?? $value;
    }

    private function colLetter(int $n): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($n);
    }

    private function styleRange(Worksheet $sheet, string $from, string $to, string $bgColor): void
    {
        $sheet->getStyle("{$from}:{$to}")->applyFromArray([
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF' . $bgColor],
            ],
            'font' => [
                'name'  => 'Arial',
                'bold'  => true,
                'size'  => 10,
                'color' => ['argb' => 'FF' . self::COLOR_TEXT],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['argb' => 'FF' . $bgColor],
                ],
            ],
        ]);
    }
}
