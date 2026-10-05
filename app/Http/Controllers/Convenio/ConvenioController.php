<?php

namespace App\Http\Controllers\Convenio;

use App\Http\Controllers\Controller;
use App\Models\Convenio;
use App\Models\ConvenioAdenda;
use App\Models\ConvenioCompromiso;
use App\Models\ConvenioContacto;
use App\Models\ConvenioGestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConvenioController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->input('pageSize', 12), 100);
        $search = trim((string) $request->input('search', ''));
        $estado = trim((string) $request->input('estado', ''));

        $query = Convenio::withCount([
            'contactos',
            'compromisos',
            'adendas',
            'archivos',
            'compromisos as realizados_count' => fn ($q) => $q->where('realizado', 'SI'),
        ])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('institucion', 'LIKE', "%{$search}%")
                        ->orWhere('nombre_convenio', 'LIKE', "%{$search}%")
                        ->orWhere('objeto', 'LIKE', "%{$search}%");
                });
            })
            ->when($estado !== '', fn ($q) => $q->where('estado', $estado))
            ->orderByDesc('id');

        $data = $query->paginate($perPage);

        $hoy = now()->startOfDay();
        $data->getCollection()->transform(function ($c) use ($hoy) {
            $venc = $c->vencimiento ? \Carbon\Carbon::parse($c->vencimiento)->startOfDay() : null;

            return [
                'id' => $c->id,
                'institucion' => $c->institucion,
                'nombre_convenio' => $c->nombre_convenio,
                'objeto' => $c->objeto,
                'fecha_emision' => $c->fecha_emision?->format('Y-m-d'),
                'inicio_vigencia' => $c->inicio_vigencia?->format('Y-m-d'),
                'vencimiento' => $c->vencimiento?->format('Y-m-d'),
                'tipo_renovacion' => $c->tipo_renovacion,
                'plazo_renovacion_anios' => $c->plazo_renovacion_anios,
                'estado' => $c->estado,
                'dias_restantes' => $venc ? $hoy->diffInDays($venc, false) : null,
                'contactos_count' => $c->contactos_count,
                'compromisos_count' => $c->compromisos_count,
                'archivos_count' => $c->archivos_count,
                'realizados_count' => $c->realizados_count,
                'avance_pct' => $c->compromisos_count > 0
                    ? (int) round($c->realizados_count * 100 / $c->compromisos_count)
                    : 0,
                'adendas_count' => $c->adendas_count,
                'created_at' => $c->created_at?->format('Y-m-d H:i'),
            ];
        });

        $resumen = Convenio::selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return response()->json([
            'status' => 200,
            'data' => $data,
            'resumen' => [
                'total' => (int) Convenio::count(),
                'vigente' => (int) ($resumen['VIGENTE'] ?? 0),
                'por_vencer' => (int) ($resumen['POR VENCER'] ?? 0),
                'vencido' => (int) ($resumen['VENCIDO'] ?? 0),
                'resuelto' => (int) ($resumen['RESUELTO'] ?? 0),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate($this->reglas());

        try {
            $convenio = DB::transaction(function () use ($request) {
                $convenio = Convenio::create($this->datosConvenio($request));
                $this->sincronizarHijos($convenio, $request);

                return $convenio;
            });

            return response()->json([
                'status' => 200,
                'message' => 'Convenio registrado correctamente.',
                'data' => $convenio->load(['contactos', 'compromisos', 'adendas', 'gestion']),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al registrar convenio: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error al registrar el convenio.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $convenio = Convenio::with(['contactos', 'compromisos', 'adendas', 'gestion'])->find($id);

        if (! $convenio) {
            return response()->json([
                'status' => 404,
                'message' => 'Convenio no encontrado.',
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'data' => $convenio,
        ]);
    }

    public function update(Request $request, $id)
    {
        $convenio = Convenio::find($id);

        if (! $convenio) {
            return response()->json([
                'status' => 404,
                'message' => 'Convenio no encontrado.',
            ], 404);
        }

        $request->validate($this->reglas());

        try {
            DB::transaction(function () use ($request, $convenio) {
                $convenio->update($this->datosConvenio($request));

                // Se reemplazan los hijos por lo enviado (lo que se quitó en el front se borra)
                $convenio->contactos()->delete();
                $convenio->compromisos()->delete();
                $convenio->adendas()->delete();

                $this->sincronizarHijos($convenio, $request, true);
            });

            return response()->json([
                'status' => 200,
                'message' => 'Convenio actualizado correctamente.',
                'data' => $convenio->fresh()->load(['contactos', 'compromisos', 'adendas', 'gestion']),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar convenio: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error al actualizar el convenio.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function reglas(): array
    {
        return [
            'institucion' => 'required|string|max:255',
            'nombreConvenio' => 'required|string|max:255',
            'objeto' => 'required|string',
            'contactos' => 'nullable|array',
            'contactos.*.nombre' => 'nullable|string|max:255',
            'contactos.*.correo' => 'nullable|email|max:255',
            'contactos.*.celular' => 'nullable|string|max:20',
            'compromisos' => 'nullable|array',
            'compromisos.*' => 'nullable|array',
            'compromisos.*.*' => 'nullable|string',
            'fechaEmision' => 'required|date',
            'inicioVigencia' => 'required|date',
            'tipoRenovacion' => 'required|in:automatico,coordinada',
            'plazoRenovacionAnios' => 'required|integer|min:1|max:99',
            'adendas' => 'nullable|array',
            'adendas.*.numero' => 'nullable|integer|min:1',
            'adendas.*.anios' => 'nullable|integer|min:0|max:99',
            'adendas.*.desde' => 'nullable|date',
            'adendas.*.hasta' => 'nullable|date',
            'vencimiento' => 'required|date|after_or_equal:inicioVigencia',
            'status' => 'nullable|string|max:50',
            'meta' => 'nullable|string',
            'financiamiento' => 'nullable|string',
            'planActividades' => 'nullable|string',
            'planValidado' => 'nullable|boolean',
            'plazoReportes' => 'nullable|string',
            'paraResolucion' => 'nullable|string',
            'responsableProduce' => 'nullable|integer|exists:users,id',
            'responsableContraparte' => 'nullable|string|max:255',
            'responsableContraparteCargo' => 'nullable|string|max:255',
            'responsableContraparteCorreo' => 'nullable|email|max:255',
            'responsableContraparteCelular' => 'nullable|string|max:20',
            'avances' => 'nullable|string',
            'observaciones' => 'nullable|string',
        ];
    }

    private function datosConvenio(Request $request): array
    {
        // El front maneja 'automatico'/'coordinada'; la BD solo admite 'manual'/'automatico'
        return [
            'institucion' => $request->input('institucion'),
            'nombre_convenio' => $request->input('nombreConvenio'),
            'objeto' => $request->input('objeto'),
            'fecha_emision' => $request->input('fechaEmision'),
            'inicio_vigencia' => $request->input('inicioVigencia'),
            'tipo_renovacion' => $request->input('tipoRenovacion') === 'automatico' ? 'automatico' : 'manual',
            'plazo_renovacion_anios' => $request->input('plazoRenovacionAnios'),
            'vencimiento' => $request->input('vencimiento'),
            'estado' => $request->input('status'),
        ];
    }

    private function sincronizarHijos(Convenio $convenio, Request $request, bool $esActualizacion = false): void
    {
        // Contactos (solo los que traigan algún dato)
        foreach ((array) $request->input('contactos', []) as $tipo => $c) {
            if (! in_array($tipo, ConvenioContacto::TIPOS, true)) {
                continue;
            }
            $nombre = trim($c['nombre'] ?? '');
            $correo = trim($c['correo'] ?? '');
            $celular = trim($c['celular'] ?? '');
            if ($nombre === '' && $correo === '' && $celular === '') {
                continue;
            }
            ConvenioContacto::create([
                'convenio_id' => $convenio->id,
                'tipo' => $tipo,
                'nombre' => $nombre !== '' ? $nombre : null,
                'correo' => $correo !== '' ? $correo : null,
                'celular' => $celular !== '' ? $celular : null,
            ]);
        }

        // Compromisos (solo textos no vacíos, con orden correlativo por tipo)
        foreach (ConvenioCompromiso::TIPOS as $tipo) {
            $orden = 1;
            foreach ((array) $request->input("compromisos.{$tipo}", []) as $texto) {
                $texto = trim((string) $texto);
                if ($texto === '') {
                    continue;
                }
                ConvenioCompromiso::create([
                    'convenio_id' => $convenio->id,
                    'tipo' => $tipo,
                    'compromiso' => $texto,
                    'orden' => $orden++,
                ]);
            }
        }

        // Adendas (solo con rango de fechas)
        foreach ((array) $request->input('adendas', []) as $i => $a) {
            if (empty($a['desde']) || empty($a['hasta'])) {
                continue;
            }
            ConvenioAdenda::create([
                'convenio_id' => $convenio->id,
                'numero' => $a['numero'] ?? ($i + 1),
                'anios' => $a['anios'] ?? 0,
                'desde' => $a['desde'],
                'hasta' => $a['hasta'],
            ]);
        }

        // Gestión (solo si trae algún dato del paso 5; si se vació todo, se elimina)
        $tieneGestion = ! is_null($request->input('planValidado'))
            || trim((string) $request->input('meta', '')) !== ''
            || trim((string) $request->input('financiamiento', '')) !== ''
            || trim((string) $request->input('planActividades', '')) !== ''
            || trim((string) $request->input('plazoReportes', '')) !== ''
            || trim((string) $request->input('paraResolucion', '')) !== ''
            || trim((string) $request->input('responsableContraparte', '')) !== ''
            || trim((string) $request->input('responsableContraparteCargo', '')) !== ''
            || trim((string) $request->input('responsableContraparteCorreo', '')) !== ''
            || trim((string) $request->input('responsableContraparteCelular', '')) !== ''
            || trim((string) $request->input('avances', '')) !== ''
            || trim((string) $request->input('observaciones', '')) !== ''
            || ! empty($request->input('responsableProduce'));

        if ($tieneGestion) {
            $responsableProduce = $request->input('responsableProduce');
            ConvenioGestion::updateOrCreate(
                ['convenio_id' => $convenio->id],
                [
                    'meta' => $request->input('meta'),
                    'financiamiento' => $request->input('financiamiento'),
                    'plan_actividades' => $request->input('planActividades'),
                    'plan_validado' => (bool) $request->input('planValidado', false),
                    'plazo_reportes' => $request->input('plazoReportes'),
                    'para_resolucion' => $request->input('paraResolucion'),
                    'responsable_produce' => $responsableProduce !== '' ? $responsableProduce : null,
                    'responsable_contraparte' => $request->input('responsableContraparte'),
                    'responsable_contraparte_cargo' => $request->input('responsableContraparteCargo'),
                    'responsable_contraparte_correo' => $request->input('responsableContraparteCorreo'),
                    'responsable_contraparte_celular' => $request->input('responsableContraparteCelular'),
                    'avances' => $request->input('avances'),
                    'observaciones' => $request->input('observaciones'),
                ]
            );
        } elseif ($esActualizacion) {
            $convenio->gestion()?->delete();
        }
    }
}
