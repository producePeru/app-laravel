<?php

namespace App\Http\Controllers\Pnte;

use App\Http\Controllers\Controller;
use App\Jobs\SendConfirmacionActividadesPP093Job;
use App\Mail\ConfirmacionRegistroPP093Mail;
use App\Models\ActividadPnte;
use App\Models\Empresario;
use App\Models\EmpresarioActividad;
use App\Models\PpCapacitador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Exception;

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
            'data' => $data
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombres_apellidos' => 'required|string|max:255',
            'dni' => 'nullable|string|max:20|unique:pp_capacitadores,dni',
            'correo' => 'nullable|email|max:255'
        ]);

        $capacitador = PpCapacitador::create([
            'nombres_apellidos' => trim($request->nombres_apellidos),
            'dni' => trim($request->dni),
            'correo' => trim($request->correo)
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Capacitador registrado correctamente.',
            'data' => $capacitador
        ]);
    }

    public function update(Request $request, $id)
    {
        $capacitador = PpCapacitador::findOrFail($id);

        $request->validate([
            'nombres_apellidos' => 'required|string|max:255',
            'dni' => 'nullable|string|max:20|unique:pp_capacitadores,dni,' . $id,
            'correo' => 'nullable|email|max:255'
        ]);

        $capacitador->update([
            'nombres_apellidos' => trim($request->nombres_apellidos),
            'dni' => trim($request->dni),
            'correo' => trim($request->correo)
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Capacitador actualizado correctamente.',
            'data' => $capacitador
        ]);
    }



    public function isRegisterPlataforma(Request $request)
    {
        $request->validate([
            'ruc'                                => 'required|string',
            'numero_dni'                         => 'required|string',
            'actividades'                        => 'required|array|min:1',
            'actividades.*.slug'                 => 'required|string',
            'actividades.*.fecha_seleccionada'   => 'required|date_format:Y-m-d',
            'actividades.*.horario_inicio'       => 'required|string',
            'actividades.*.horario_fin'          => 'required|string',
        ]);

        // 1. Buscamos el último registro del empresario usando el RUC y DNI
        $empresario = Empresario::where('ruc', $request->ruc)
            ->where('numero_dni', $request->numero_dni)
            ->latest('id')
            ->first();

        // 🚨 REGLA: Si el empresario NO existe en absoluto en el sistema
        if (!$empresario) {
            return response()->json([
                'status'          => 201,
                'is_registered'   => false, // Flag para saber que es un usuario sin registro anterior
                'empresario_id'   => null,
                'has_duplicates'  => false,
                'message'         => 'Usuario sin registro previo en el sistema. Puede proceder a llenar todo el formulario.',
                'duplicados'      => []
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
                    'slug'               => $act['slug'],
                    'fecha_seleccionada' => $act['fecha_seleccionada'],
                    'horario_inicio'     => $act['horario_inicio'],
                    'horario_fin'        => $act['horario_fin'],
                    'texto_auxiliar'     => "Código: {$act['slug']} para el día {$fechaHumana} de {$act['horario_inicio']} a {$act['horario_fin']}."
                ];
            }
        }

        if (!empty($duplicadosEncontrados)) {

            if (count($duplicadosEncontrados) === 1) {
                $mensajeBonito = "Estimado usuario, usted ya se encuentra registrado en la siguiente actividad: " . $duplicadosEncontrados[0]['texto_auxiliar'];
            } else {
                $mensajeBonito = "Estimado usuario, detectamos que ya se encuentra inscrito en las siguientes actividades seleccionadas: \n";
                foreach ($duplicadosEncontrados as $index => $dup) {
                    $mensajeBonito .= ($index + 1) . ") " . $dup['texto_auxiliar'] . "\n";
                }
            }

            return response()->json([
                'status'          => 409,
                'is_registered'   => true,
                'empresario_id'   => $empresarioId, // Retornamos el último ID del usuario
                'has_duplicates'  => true,
                'message'         => $mensajeBonito,
                'duplicados'      => $duplicadosEncontrados
            ]);
        }

        return response()->json([
            'status'          => 200,
            'is_registered'   => true,
            'empresario_id'   => $empresarioId, // Retornamos el último ID del usuario
            'has_duplicates'  => false,
            'message'         => 'El usuario ya cuenta con un registro en el sistema, pero las actividades seleccionadas están disponibles.',
            'duplicados'      => []
        ], 200);
    }

    protected function enviarCorreoPP093(Request $request)
    {
        try {
            // Definimos el remitente / configuración de correo que queremos usar (ej: 'digitalizacion')
            $mailer = 'hostinger';

            // Convertimos todo el contenido del Request actual a un array para enviarlo seguro al Job
            $payloadData = $request->all();

            // Despachamos el Job para que procese el envío en segundo plano
            SendConfirmacionActividadesPP093Job::dispatch($payloadData, $mailer);
        } catch (\Exception $e) {
            // Si hay un error al meter el trabajo a la cola, lo registramos sin romper el flujo principal
            Log::error("Error al despachar el Job de correo para {$request->correo_electronico}: " . $e->getMessage());
        }
    }
}
