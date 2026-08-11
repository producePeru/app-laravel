<?php

namespace App\Http\Controllers\Pnte;

use App\Http\Controllers\Controller;
use App\Jobs\SendConfirmacionActividadesPP093Job;
use App\Mail\FinalizacionTestSalidaMail;
use App\Models\ActividadPnte;
use App\Models\Empresario;
use App\Models\EmpresarioActividad;
use App\Models\PpCapacitador;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PpCapacitadorController extends Controller
{
    public function index(Request $request)
    {
        $pageSize = $request->input('pageSize', 10);

        $data = PpCapacitador::query()

            ->when(
                $request->filled('name'),
                function ($q) use ($request) {

                    $name = trim($request->name);

                    $q->where(function ($query) use ($name) {

                        $query->where(
                            'nombres_apellidos',
                            'LIKE',
                            "%{$name}%"
                        )
                            ->orWhere(
                                'dni',
                                'LIKE',
                                "%{$name}%"
                            )
                            ->orWhere(
                                'correo',
                                'LIKE',
                                "%{$name}%"
                            );
                    });
                }
            )

            ->orderByDesc('id')
            ->paginate($pageSize);

        return response()->json([
            'status' => 200,
            'message' => 'Capacitadores obtenidos correctamente.',
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombres_apellidos' => 'required|string|max:255',
            'dni' => 'nullable|string|max:20|unique:pp_capacitadores,dni',
            'correo' => 'nullable|email|max:255',
        ]);

        $capacitador = PpCapacitador::create([
            'nombres_apellidos' => trim($request->nombres_apellidos),
            'dni' => trim($request->dni),
            'correo' => trim($request->correo),
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Capacitador registrado correctamente.',
            'data' => $capacitador,
        ]);
    }

    public function update(Request $request, $id)
    {
        $capacitador = PpCapacitador::findOrFail($id);

        $request->validate([
            'nombres_apellidos' => 'required|string|max:255',
            'dni' => 'nullable|string|max:20|unique:pp_capacitadores,dni,'.$id,
            'correo' => 'nullable|email|max:255',
        ]);

        $capacitador->update([
            'nombres_apellidos' => trim($request->nombres_apellidos),
            'dni' => trim($request->dni),
            'correo' => trim($request->correo),
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Capacitador actualizado correctamente.',
            'data' => $capacitador,
        ]);
    }

    public function isRegisterPlataforma(Request $request)
    {
        $request->validate([
            'ruc' => 'required|string',
            'numero_dni' => 'required|string',
            'actividades' => 'required|array|min:1',
            'actividades.*.slug' => 'required|string',
            'actividades.*.fecha_seleccionada' => 'required|date_format:Y-m-d',
            'actividades.*.horario_inicio' => 'required|string',
            'actividades.*.horario_fin' => 'required|string',
        ]);

        // 1. Buscamos el último registro del empresario usando el RUC y DNI
        $empresario = Empresario::where('ruc', $request->ruc)
            ->where('numero_dni', $request->numero_dni)
            ->latest('id')
            ->first();

        // 🚨 REGLA: Si el empresario NO existe en absoluto en el sistema
        if (! $empresario) {
            return response()->json([
                'status' => 201,
                'is_registered' => false, // Flag para saber que es un usuario sin registro anterior
                'empresario_id' => null,
                'has_duplicates' => false,
                'message' => 'Usuario sin registro previo en el sistema. Puede proceder a llenar todo el formulario.',
                'duplicados' => [],
            ]);
        }

        // Si llegamos aquí, el usuario SÍ existe. Capturamos su último ID generado.
        $empresarioId = $empresario->id;
        $duplicadosEncontrados = [];

        // 2. Iteramos las actividades para buscar colisiones de horarios exactas
        foreach ($request->actividades as $act) {

            $existe = EmpresarioActividad::where('slug', $act['slug'])
                ->where('empresario_id', $empresarioId)
                ->where('fecha_seleccionada', $act['fecha_seleccionada'])
                ->where('horario_inicio', $act['horario_inicio'])
                ->where('horario_fin', $act['horario_fin'])
                ->exists();

            if ($existe) {
                $fechaHumana = date('d/m/Y', strtotime($act['fecha_seleccionada']));

                $duplicadosEncontrados[] = [
                    'slug' => $act['slug'],
                    'fecha_seleccionada' => $act['fecha_seleccionada'],
                    'horario_inicio' => $act['horario_inicio'],
                    'horario_fin' => $act['horario_fin'],
                    'texto_auxiliar' => "Código: {$act['slug']} para el día {$fechaHumana} de {$act['horario_inicio']} a {$act['horario_fin']}.",
                ];
            }
        }

        if (! empty($duplicadosEncontrados)) {

            if (count($duplicadosEncontrados) === 1) {
                $mensajeBonito = 'Estimado usuario, usted ya se encuentra registrado en la siguiente actividad: '.$duplicadosEncontrados[0]['texto_auxiliar'];
            } else {
                $mensajeBonito = "Estimado usuario, detectamos que ya se encuentra inscrito en las siguientes actividades seleccionadas: \n";
                foreach ($duplicadosEncontrados as $index => $dup) {
                    $mensajeBonito .= ($index + 1).') '.$dup['texto_auxiliar']."\n";
                }
            }

            return response()->json([
                'status' => 409,
                'is_registered' => true,
                'empresario_id' => $empresarioId, // Retornamos el último ID del usuario
                'has_duplicates' => true,
                'message' => $mensajeBonito,
                'duplicados' => $duplicadosEncontrados,
            ]);
        }

        return response()->json([
            'status' => 200,
            'is_registered' => true,
            'empresario_id' => $empresarioId, // Retornamos el último ID del usuario
            'has_duplicates' => false,
            'message' => 'El usuario ya cuenta con un registro en el sistema, pero las actividades seleccionadas están disponibles.',
            'duplicados' => [],
        ], 200);
    }

    protected function enviarCorreoPP093(Request $request)
    {
        try {

            $mailer = 'hostinger3k';

            $payloadData = $request->all();

            SendConfirmacionActividadesPP093Job::dispatch($payloadData, $mailer);
        } catch (\Exception $e) {
            Log::error("Error al despachar el Job de correo para {$request->correo_electronico}: ".$e->getMessage());
        }
    }

    protected function enviarCorreoFinalizadoTestSalida(Request $request)
    {
        try {

            $request->validate([
                'empresario_id' => 'required|integer|exists:empresarios,id',
            ]);

            $empresario = Empresario::select(
                'id',
                'nombres',
                'apellido_paterno',
                'apellido_materno',
                'correo_electronico'
            )
                ->find($request->empresario_id);

            if (empty($empresario->correo_electronico)) {
                Log::warning("El empresario {$request->empresario_id} no tiene correo electrónico registrado.");

                return response()->json([
                    'status' => 404,
                    'message' => 'El empresario no tiene un correo electrónico registrado.',
                ], 404);
            }

            Mail::mailer('hostinger3k')
                ->to($empresario->correo_electronico)
                ->bcc('capacitaciones_tuempresa@produce.gob.pe')
                ->send(new FinalizacionTestSalidaMail([
                    'nombres' => trim(
                        "{$empresario->nombres} {$empresario->apellido_paterno} {$empresario->apellido_materno}"
                    ),
                ]));

            return response()->json([
                'status' => 200,
                'message' => 'Correo enviado correctamente.',
            ]);
        } catch (\Exception $e) {

            Log::error(
                "Error enviando correo de finalización al empresario {$request->empresario_id}: {$e->getMessage()}"
            );

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error al enviar el correo.',
            ], 500);
        }
    }

    // calendario
    public function calendar(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'nullable|integer|digits:4',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        $componentes = [
            1 => [
                'nombre' => 'ACCESO AL FINANCIAMIENTO',
                'color' => 'success', // verde
            ],
            2 => [
                'nombre' => 'DESARROLLO PRODUCTIVO',
                'color' => 'processing', // azul
            ],
            3 => [
                'nombre' => 'DIGITALIZACIÓN',
                'color' => 'warning', // amarillo
            ],
            4 => [
                'nombre' => 'GESTIÓN EMPRESARIAL',
                'color' => 'error', // rojo
            ],
        ];

        $actividades = ActividadPnte::with([
            'representante:id,name,lastname,middlename',
        ])
            ->select([
                'id',
                'fechas',
                'tema',
                'link',
                'representante_id',
                'horario',
                'componente_id',
            ])
            ->where('unidad', 2)
            ->where('tipo_actividad_id', 6)
            ->when($request->filled('year') || $request->filled('month'), function ($q) use ($request) {
                $year = $request->filled('year') ? $request->year : '%';
                $month = $request->filled('month')
                    ? str_pad($request->month, 2, '0', STR_PAD_LEFT)
                    : '%';

                $pattern = "{$year}-{$month}-%";

                $q->whereRaw("JSON_SEARCH(fechas, 'one', ?) IS NOT NULL", [$pattern]);
            })
            ->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(fechas, '$[0]')) ASC")
            ->get()
            ->map(function ($item) use ($componentes) {
                $componente = $componentes[$item->componente_id] ?? [
                    'nombre' => 'SIN COMPONENTE',
                    'color' => 'default',
                ];

                return [
                    'id' => $item->id,
                    'fechas' => $item->fechas,
                    'tema' => $item->tema,
                    'horario' => $item->horario,
                    'link' => $item->link,
                    'componente_id' => $item->componente_id,
                    'componente' => $componente['nombre'],
                    'color' => $componente['color'],
                    'representante' => $item->representante,
                ];
            });

        return response()->json([
            'status' => 200,
            'message' => 'Calendario obtenido correctamente.',
            'data' => $actividades,
        ]);
    }
}
