<?php

namespace App\Http\Controllers\Convenio;

use App\Http\Controllers\Controller;
use App\Models\Convenio;
use App\Models\ConvenioCompromiso;
use App\Models\ConvenioCompromisoAvance;
use App\Models\ConvenioCompromisoEvidencia;
use App\Models\User;
use App\Services\ConvenioCortes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ConvenioSeguimientoController extends Controller
{
    /**
     * Seguimiento del convenio: cabecera + cortes semestrales (30/06 y 31/12)
     * + compromisos agrupados por tipo, cada uno con sus avances por corte
     * (SE REALIZÓ, ACTIVIDAD y MEDIOS DE VERIFICACIÓN).
     *
     * Cada corte empieza vacío: al cambiar de corte los textareas se limpian
     * y se vuelve a completar. Pasada la fecha de fin (extendida por adendas)
     * el convenio se bloquea y es de solo lectura.
     */
    public function seguimiento($convenioId)
    {
        $convenio = Convenio::select(
            'id', 'institucion', 'nombre_convenio', 'objeto',
            'fecha_emision', 'inicio_vigencia', 'vencimiento',
            'tipo_renovacion', 'estado'
        )->with('adendas')->find($convenioId);

        if (! $convenio) {
            return response()->json([
                'status' => 404,
                'message' => 'Convenio no encontrado.',
            ], 404);
        }

        $resumen = ConvenioCortes::resumen($convenio);

        $compromisos = ConvenioCompromiso::with(['avances.medios', 'medios'])
            ->where('convenio_id', $convenio->id)
            ->orderBy('tipo')
            ->orderBy('orden')
            ->get();

        // Garantiza una fila de avance por cada (compromiso, corte).
        foreach ($compromisos as $compromiso) {
            foreach ($resumen['cortes'] as $corte) {
                ConvenioCompromisoAvance::updateOrCreate(
                    ['compromiso_id' => $compromiso->id, 'corte' => $corte['numero']],
                    ['desde' => $corte['desde'], 'hasta' => $corte['hasta']]
                );
            }
            $compromiso->load('avances.medios');
        }

        // Evidencia de quién llenó cada actividad / subió cada archivo.
        $userIds = $compromisos
            ->flatMap(fn ($c) => $c->avances->pluck('user_id')
                ->merge($c->avances->flatMap(fn ($a) => $a->medios->pluck('user_id')))
                ->merge($c->medios->pluck('user_id')))
            ->filter()->unique()->values();
        $usuarios = $userIds->isNotEmpty()
            ? User::whereIn('id', $userIds)->get()->keyBy('id')
            : collect();

        $data = $compromisos
            ->groupBy('tipo')
            ->map(fn ($items) => $items->map(fn ($c) => [
                'id' => $c->id,
                'tipo' => $c->tipo,
                'orden' => $c->orden,
                'compromiso' => $c->compromiso,
                'avances' => $c->avances->sortBy('corte')->values()->map(
                    fn ($a) => $this->formatoAvance($c, $a, $usuarios)
                )->values(),
            ])->values());

        $convenioArray = $convenio->toArray();
        $convenioArray['fin_efectivo'] = $resumen['fin_efectivo'];

        return response()->json([
            'status' => 200,
            'convenio' => $convenioArray,
            'compromisos' => $data,
            'tipos' => ConvenioCompromiso::TIPOS,
            'cortes' => $resumen['cortes'],
            'corte_actual' => $resumen['corte_actual'],
            'bloqueado' => $resumen['bloqueado'],
            'hoy' => $resumen['hoy'],
        ]);
    }

    /**
     * Actualiza SE REALIZÓ (SI/NO) y ACTIVIDAD del avance de un corte.
     */
    public function actualizarCompromiso(Request $request, $id)
    {
        $request->validate([
            'realizado' => 'nullable|in:SI,NO',
            'actividad' => 'nullable|string',
            'corte' => 'required|integer|min:1',
        ]);

        $compromiso = ConvenioCompromiso::with('convenio.adendas')->find($id);

        if (! $compromiso) {
            return response()->json([
                'status' => 404,
                'message' => 'Compromiso no encontrado.',
            ], 404);
        }

        $corte = (int) $request->input('corte');
        $bloqueo = $this->motivoBloqueo($compromiso->convenio, $corte);
        if ($bloqueo) {
            return response()->json(['status' => 403, 'message' => $bloqueo], 403);
        }

        $periodo = $this->periodoCorte($compromiso->convenio, $corte);

        $avance = ConvenioCompromisoAvance::updateOrCreate(
            ['compromiso_id' => $compromiso->id, 'corte' => $corte],
            [
                'desde' => $periodo['desde'] ?? null,
                'hasta' => $periodo['hasta'] ?? null,
                'realizado' => $request->input('realizado') ?: null,
                'actividad' => $request->input('actividad') ?: null,
                'user_id' => $request->user()?->id,
            ]
        );

        // Sincroniza las columnas planas con el corte actual para que los
        // contadores del listado sigan funcionando.
        $compromiso->update([
            'realizado' => $avance->realizado,
            'actividad' => $avance->actividad,
        ]);

        $usuario = $request->user();
        $usuarios = $usuario ? collect([$usuario->id => $usuario]) : collect();

        return response()->json([
            'status' => 200,
            'message' => 'Seguimiento actualizado.',
            'data' => $this->formatoAvance($compromiso, $avance->fresh('medios'), $usuarios),
        ]);
    }

    /**
     * Sube MEDIOS DE VERIFICACIÓN del avance de un corte:
     * imagen, PDF, Word, Excel, PowerPoint u otro.
     * Máximo 5 archivos por compromiso y corte.
     */
    public function subirMedios(Request $request, $id)
    {
        $request->validate([
            'files' => 'required|array|max:5',
            'files.*' => 'required|file|mimes:jpg,jpeg,png,webp,gif,doc,docx,xls,xlsx,ppt,pptx,pdf,txt,csv|max:10240',
            'corte' => 'required|integer|min:1',
        ]);

        $compromiso = ConvenioCompromiso::with('convenio.adendas')->find($id);

        if (! $compromiso) {
            return response()->json([
                'status' => 404,
                'message' => 'Compromiso no encontrado.',
            ], 404);
        }

        $corte = (int) $request->input('corte');
        $bloqueo = $this->motivoBloqueo($compromiso->convenio, $corte);
        if ($bloqueo) {
            return response()->json(['status' => 403, 'message' => $bloqueo], 403);
        }

        $periodo = $this->periodoCorte($compromiso->convenio, $corte);
        $avance = ConvenioCompromisoAvance::updateOrCreate(
            ['compromiso_id' => $compromiso->id, 'corte' => $corte],
            ['desde' => $periodo['desde'] ?? null, 'hasta' => $periodo['hasta'] ?? null]
        );

        $existentes = ConvenioCompromisoEvidencia::where('avance_id', $avance->id)->count();
        if ($corte === 1) {
            // Archivos históricos sin avance asignado pertenecen al corte 1.
            $existentes += ConvenioCompromisoEvidencia::where('compromiso_id', $compromiso->id)
                ->whereNull('avance_id')->count();
        }
        $nuevos = count((array) $request->file('files'));

        if ($existentes + $nuevos > 5) {
            return response()->json([
                'status' => 422,
                'message' => "Este compromiso ya tiene {$existentes} archivo(s) en este corte. Máximo 5 en total.",
            ], 422);
        }

        try {
            $dir = public_path('storage/convenios/evidencias');
            if (! File::exists($dir)) {
                File::makeDirectory($dir, 0775, true, true);
            }

            $guardados = [];
            foreach ((array) $request->file('files') as $file) {
                // Leer todo del temporal ANTES de moverlo: luego ya no existe.
                $ext = strtolower($file->getClientOriginalExtension());
                $original = $file->getClientOriginalName();
                $mime = $file->getMimeType();
                $tamano = $file->getSize();
                $nombre = (string) Str::uuid().'.'.$ext;
                $file->move($dir, $nombre);

                $guardados[] = ConvenioCompromisoEvidencia::create([
                    'compromiso_id' => $compromiso->id,
                    'avance_id' => $avance->id,
                    'user_id' => $request->user()?->id,
                    'nombre_original' => $original,
                    'ruta' => 'storage/convenios/evidencias/'.$nombre,
                    'mime' => $mime,
                    'tamano' => $tamano,
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Archivo(s) cargado(s) correctamente.',
                'data' => $guardados,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al subir medios de verificación: '.$e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error al subir los archivos.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Elimina un medio de verificación (archivo + registro).
     * Bloqueado si el convenio finalizó o el archivo es de un corte cerrado.
     */
    public function eliminarMedio($id)
    {
        $medio = ConvenioCompromisoEvidencia::with('compromiso.convenio.adendas')->find($id);

        if (! $medio) {
            return response()->json([
                'status' => 404,
                'message' => 'Archivo no encontrado.',
            ], 404);
        }

        $corte = $medio->avance?->corte ?? 1;
        $bloqueo = $this->motivoBloqueo($medio->compromiso->convenio, (int) $corte);
        if ($bloqueo) {
            return response()->json(['status' => 403, 'message' => $bloqueo], 403);
        }

        $absoluta = public_path($medio->ruta);
        if (File::exists($absoluta)) {
            File::delete($absoluta);
        }
        $medio->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Archivo eliminado.',
        ]);
    }

    // ─── Ayudas ───────────────────────────────────────────────────

    private function motivoBloqueo($convenio, int $corte): ?string
    {
        if (! $convenio) {
            return 'Convenio no encontrado.';
        }

        $resumen = ConvenioCortes::resumen($convenio);

        if ($resumen['bloqueado']) {
            $fin = $resumen['fin_efectivo']
                ? \Carbon\Carbon::parse($resumen['fin_efectivo'])->format('d/m/Y')
                : '';

            return "El convenio finalizó el {$fin}. Ya no se puede editar.";
        }

        foreach ($resumen['cortes'] as $c) {
            if ((int) $c['numero'] === $corte) {
                return ! empty($c['es_editable'])
                    ? null
                    : 'Este corte no está vigente. Solo se puede completar el corte actual.';
            }
        }

        return 'Corte no válido para este convenio.';
    }

    private function periodoCorte($convenio, int $corte): array
    {
        $resumen = ConvenioCortes::resumen($convenio);
        foreach ($resumen['cortes'] as $c) {
            if ((int) $c['numero'] === $corte) {
                return ['desde' => $c['desde'], 'hasta' => $c['hasta']];
            }
        }

        return ['desde' => null, 'hasta' => null];
    }

    private function nombreUsuario($usuarios, $userId): ?string
    {
        $u = $usuarios[$userId] ?? null;
        if (! $u) {
            return null;
        }

        return trim(implode(' ', array_filter([$u->name ?? null, $u->lastname ?? null, $u->middlename ?? null]))) ?: null;
    }

    private function formatoMedio($m, $usuarios = []): array
    {
        return [
            'id' => $m->id,
            'nombre' => $m->nombre_original,
            'url' => asset($m->ruta),
            'mime' => $m->mime,
            'tamano' => $m->tamano,
            'es_imagen' => str_starts_with((string) $m->mime, 'image/'),
            'created_at' => $m->created_at?->format('d/m/Y H:i'),
            // Evidencia de quién subió el archivo (de momento no se muestra).
            'user_id' => $m->user_id ?? null,
            'subido_por' => $this->nombreUsuario($usuarios, $m->user_id ?? null),
        ];
    }

    private function formatoAvance($compromiso, $avance, $usuarios = []): array
    {
        $medios = collect($avance->medios ?? [])->map(fn ($m) => $this->formatoMedio($m, $usuarios))->values();

        if ((int) $avance->corte === 1) {
            // Archivos históricos sin avance asignado pertenecen al corte 1.
            $legados = ($compromiso->relationLoaded('medios') ? $compromiso->medios : collect())
                ->whereNull('avance_id')
                ->map(fn ($m) => $this->formatoMedio($m, $usuarios))->values();
            $medios = $medios->concat($legados)->values();
        }

        return [
            'id' => $avance->id,
            'corte' => (int) $avance->corte,
            'desde' => $avance->desde,
            'hasta' => $avance->hasta,
            'realizado' => $avance->realizado ?? (($avance->corte == 1) ? $compromiso->realizado : null),
            'actividad' => $avance->actividad ?? (($avance->corte == 1) ? $compromiso->actividad : null),
            'medios' => $medios,
            // Evidencia de quién llenó la actividad (de momento no se muestra).
            'user_id' => $avance->user_id ?? null,
            'registrado_por' => $this->nombreUsuario($usuarios, $avance->user_id ?? null),
        ];
    }
}
