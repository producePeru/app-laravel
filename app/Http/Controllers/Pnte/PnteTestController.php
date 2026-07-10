<?php

namespace App\Http\Controllers\Pnte;

use App\Http\Controllers\Controller;
use App\Models\ActividadPnte;
use App\Models\Empresario;
use App\Models\EmpresarioActividad;
use App\Models\PntTest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PnteTestController extends Controller
{
    public function saveTest(Request $request)
    {
        $request->validate([
            'slug'           => 'required|string|max:255',
            'test_entrada'   => 'nullable|array',
            'test_salida'    => 'nullable|array',
            'caso_practico'  => 'nullable|string',
        ]);

        try {

            DB::beginTransaction();

            // Buscar por slug o crear un registro vacío
            $test = PntTest::firstOrCreate(
                ['slug' => $request->slug],
                [
                    'test_entrada'  => null,
                    'test_salida'   => null,
                    'caso_practico' => null,
                ]
            );

            $data = [];

            // Solo actualizar los campos enviados
            if ($request->has('test_entrada')) {
                $data['test_entrada'] = $request->test_entrada;
            }

            if ($request->has('test_salida')) {
                $data['test_salida'] = $request->test_salida;
            }

            if ($request->has('caso_practico')) {
                $data['caso_practico'] = $request->caso_practico;
            }

            if (!empty($data)) {
                $test->update($data);
            }

            DB::commit();

            return response()->json([
                'status'  => 200,
                'message' => $test->wasRecentlyCreated
                    ? 'Test creado correctamente.'
                    : 'Test actualizado correctamente.',
                'data' => [
                    'id' => $test->id,
                    'slug' => $test->slug
                ]
            ], 200);
        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('saveTest: ' . $e->getMessage());

            return response()->json([
                'status'  => 500,
                'message' => 'Ocurrió un error inesperado.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function getTestEntrada($slug)
    {
        try {

            $test = PntTest::where('slug', $slug)->first();

            if (!$test) {
                return response()->json([
                    'status' => 404,
                    'message' => "No existe un test con el slug '{$slug}'."
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Test obtenido correctamente.',
                'data' => $test->test_entrada
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error inesperado.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getTestSalida($slug)
    {
        try {

            $test = PntTest::where('slug', $slug)->first();

            if (!$test) {
                return response()->json([
                    'status' => 404,
                    'message' => "No existe un test con el slug '{$slug}'."
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Test obtenido correctamente.',
                'data' => $test->caso_practico
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error inesperado.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPublicTest($slug)
    {
        try {

            $test = PntTest::where('slug', $slug)->first();

            if (!$test) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No existe un test para el slug indicado.'
                ]);
            }

            $actividad = ActividadPnte::select('tema', 'link')
                ->where('slug', $slug)
                ->first();

            $fields = collect($test->test_entrada)->map(function ($pregunta, $index) {

                return [
                    'type' => 'radio',
                    'label' => $pregunta['texto'],
                    'model' => 'pregunta_' . ($index + 1),
                    'required' => true,
                    'md' => 12,
                    'visible' => true,
                    'options' => collect($pregunta['opciones'])->map(function ($opcion) {

                        return [
                            'label' => $opcion['texto'],
                            'value' => $opcion['id']
                        ];
                    })->values()->toArray()
                ];
            })->values();

            return response()->json([
                'status' => 200,
                'message' => 'Información obtenida correctamente.',
                'data' => [
                    'slug' => $slug,
                    'tema' => optional($actividad)->tema,
                    'link' => optional($actividad)->link,
                    'fields' => $fields
                ]
            ]);
        } catch (\Exception $e) {

            Log::error('getPublicTest: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error inesperado.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPublicTestEnd($slug)
    {
        try {

            $test = PntTest::where('slug', $slug)->first();

            if (!$test) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No existe un test para el slug indicado.'
                ], 404);
            }

            $actividad = ActividadPnte::select('tema', 'link')
                ->where('slug', $slug)
                ->first();

            // Preguntas dinámicas del test
            $fields = collect($test->test_entrada)->map(function ($pregunta, $index) {

                return [
                    'type' => 'radio',
                    'label' => $pregunta['texto'],
                    'model' => 'pregunta_' . ($index + 1),
                    'required' => true,
                    'md' => 12,
                    'visible' => true,
                    'options' => collect($pregunta['opciones'])->map(function ($opcion) {
                        return [
                            'label' => $opcion['texto'],
                            'value' => $opcion['id']
                        ];
                    })->values()->toArray()
                ];
            });

            // Campos adicionales del test de salida
            $fields = $fields->concat([

                [
                    'type' => 'title',
                    'label' => 'CASO PRÁCTICO',
                    'md' => 12
                ],

                [
                    'type' => 'description',
                    'label' => $test->caso_practico ?? '',
                    'model' => 'caso_practico_title',
                    'md' => 12,
                    'visible' => true,
                ],

                [
                    'type' => 'textarea',
                    'label' => 'Respuesta del caso práctico',
                    'model' => 'caso_practico',
                    'required' => true,
                    'md' => 12,
                    'rows' => 7
                ],

                [
                    'type' => 'title',
                    'label' => 'LEE CON ATENCIÓN CADA ENUNCIADO Y MARCA',
                    'md' => 12
                ],

                [
                    'type' => 'rating2',
                    'label' => 'Se cumplió con tus expectativas personales',
                    'model' => 'rating_1',
                    'visible' => true,
                    'md' => 12,
                    'required' => true,
                    'options' => [
                        ['label' => 'Muy insatisfecho', 'value' => 1],
                        ['label' => 'Insatisfecho', 'value' => 2],
                        ['label' => 'Poco satisfecho', 'value' => 3],
                        ['label' => 'Satisfecho', 'value' => 4],
                        ['label' => 'Muy satisfecho', 'value' => 5],
                    ]
                ],
                [
                    'type' => 'rating2',
                    'label' => 'La capacitación es útil para mi trabajo',
                    'model' => 'rating_2',
                    'visible' => true,
                    'md' => 12,
                    'required' => true,
                    'options' => [
                        ['label' => 'Muy insatisfecho', 'value' => 1],
                        ['label' => 'Insatisfecho', 'value' => 2],
                        ['label' => 'Poco satisfecho', 'value' => 3],
                        ['label' => 'Satisfecho', 'value' => 4],
                        ['label' => 'Muy satisfecho', 'value' => 5],
                    ]
                ],
                [
                    'type' => 'rating2',
                    'label' => 'La calidad de la facilitación me satisface',
                    'model' => 'rating_3',
                    'visible' => true,
                    'md' => 12,
                    'required' => true,
                    'options' => [
                        ['label' => 'Muy insatisfecho', 'value' => 1],
                        ['label' => 'Insatisfecho', 'value' => 2],
                        ['label' => 'Poco satisfecho', 'value' => 3],
                        ['label' => 'Satisfecho', 'value' => 4],
                        ['label' => 'Muy satisfecho', 'value' => 5],
                    ]
                ],
                [
                    'type' => 'rating2',
                    'label' => 'La logística de la capacitación me satisface',
                    'model' => 'rating_4',
                    'visible' => true,
                    'md' => 12,
                    'required' => true,
                    'options' => [
                        ['label' => 'Muy insatisfecho', 'value' => 1],
                        ['label' => 'Insatisfecho', 'value' => 2],
                        ['label' => 'Poco satisfecho', 'value' => 3],
                        ['label' => 'Satisfecho', 'value' => 4],
                        ['label' => 'Muy satisfecho', 'value' => 5],
                    ]
                ],
                [
                    'type' => 'rating2',
                    'label' => 'Recomendaría la capacitación',
                    'model' => 'rating_5',
                    'visible' => true,
                    'md' => 12,
                    'required' => true,
                    'options' => [
                        ['label' => 'Muy insatisfecho', 'value' => 1],
                        ['label' => 'Insatisfecho', 'value' => 2],
                        ['label' => 'Poco satisfecho', 'value' => 3],
                        ['label' => 'Satisfecho', 'value' => 4],
                        ['label' => 'Muy satisfecho', 'value' => 5],
                    ]
                ],

                [
                    'type' => 'title',
                    'label' => 'SUGERENCIAS',
                    'md' => 12
                ],
                [
                    'type' => 'textarea',
                    'label' => 'Apreciaciones y/o sugerencias de mejora',
                    'model' => 'sugerencias',
                    'required' => true,
                    'md' => 12,
                    'rows' => 3
                ]

            ])->values();

            return response()->json([
                'status' => 200,
                'message' => 'Información obtenida correctamente.',
                'data' => [
                    'slug'   => $slug,
                    'tema'   => optional($actividad)->tema,
                    'link'   => optional($actividad)->link,
                    'fields' => $fields
                ]
            ]);
        } catch (\Exception $e) {

            Log::error('getPublicTestEnd: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error inesperado.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function validatePublicTest(Request $request)
    {
        $request->validate([
            'slug' => 'required|string',
            'ruc'  => 'required|string|max:11',
            'dni'  => 'required|string|max:12',
            'date' => 'required|date',
        ]);

        try {

            $registro = EmpresarioActividad::with([
                'empresario',
                'actividadPnte:id,slug,link,tema'
            ])
                ->where('slug', $request->slug)
                ->whereDate('fecha_seleccionada', $request->date)
                ->whereHas('empresario', function ($query) use ($request) {
                    $query->where('ruc', $request->ruc)
                        ->where('numero_dni', $request->dni);
                })
                ->first();

            if (!$registro) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No se encontró un participante registrado con los datos proporcionados para la fecha indicada.'
                ]);
            }

            // Validar estado del test de entrada
            if (is_null($registro->test_entrada)) {
                return response()->json([
                    'status'  => 204,
                    'aplicado' => false,
                    'message' => 'El test de entrada aún no ha sido aplicado.',
                    'data' => [
                        'empresario_id' => $registro->empresario->id,
                        'slug'          => $registro->slug,
                        'tema'          => optional($registro->actividadPnte)->tema,
                        'fecha_seleccionada' => $registro->fecha_seleccionada,
                        'horario_fin' => $registro->horario_fin,
                        'horario_inicio' => $registro->horario_inicio,
                        'ruc'           => $registro->empresario->ruc,
                        'dni'           => $registro->empresario->numero_dni,
                        'email'         => $registro->empresario->correo_electronico,
                        'link'          => optional($registro->actividadPnte)->link,
                        'nombres'       => trim(
                            $registro->empresario->nombres . ' ' .
                                $registro->empresario->apellido_paterno . ' ' .
                                $registro->empresario->apellido_materno
                        )
                    ]
                ]);
            }

            return response()->json([
                'status'   => 200,
                'aplicado' => true,
                'message'  => 'El participante ya completó el test de entrada.',
                'data' => [
                    'empresario_id' => $registro->empresario->id,
                    'slug'          => $registro->slug,
                    'tema'          => optional($registro->actividadPnte)->tema,
                    'fecha_seleccionada' => $registro->fecha_seleccionada,
                    'horario_fin' => $registro->horario_fin,
                    'horario_inicio' => $registro->horario_inicio,
                    'ruc'           => $registro->empresario->ruc,
                    'dni'           => $registro->empresario->numero_dni,
                    'email'         => $registro->empresario->correo_electronico,
                    'link'          => optional($registro->actividadPnte)->link,
                    'nombres'       => trim(
                        $registro->empresario->nombres . ' ' .
                            $registro->empresario->apellido_paterno . ' ' .
                            $registro->empresario->apellido_materno
                    )
                ]
            ]);
        } catch (\Exception $e) {

            Log::error('validatePublicTest: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error inesperado.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getEventInfo($slug)
    {
        try {

            $actividad = ActividadPnte::select('tema', 'fechas')
                ->where('slug', $slug)
                ->first();

            if (!$actividad) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No se encontró una actividad con el slug indicado.'
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Información obtenida correctamente.',
                'data' => [
                    'tema' => $actividad->tema,
                    'fechas' => $actividad->fechas
                ]
            ], 200);
        } catch (\Exception $e) {

            Log::error('getEventInfo: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error inesperado.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // del formulario public guardamos las respuestas

    public function savePublicTest(Request $request)
    {
        $request->validate([
            'slug'            => 'required|string',
            'numero_dni'      => 'required|string|max:12',
            'empresario_id'   => 'required|integer',
            'date'            => 'required|date',
            'horario_inicio'  => 'required',
            'horario_fin'     => 'required',
            'type'            => 'required|in:entrada,salida',

            'test_entrada'    => 'nullable|array',
            'test_salida'     => 'nullable|array',
            'caso_practico'   => 'nullable|string',
            'ratings'         => 'nullable|array',
            'sugerencias'     => 'nullable|string',
        ]);

        try {

            $registro = EmpresarioActividad::where('slug', $request->slug)
                ->where('numero_dni', $request->numero_dni)
                ->where('empresario_id', $request->empresario_id)
                ->whereDate('fecha_seleccionada', $request->date)
                ->where('horario_inicio', $request->horario_inicio)
                ->where('horario_fin', $request->horario_fin)
                ->first();

            if (!$registro) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No se encontró el registro del participante.'
                ]);
            }

            /*
        |--------------------------------------------------------------------------
        | TEST DE ENTRADA
        |--------------------------------------------------------------------------
        */

            if ($request->type === 'entrada') {

                if (!empty($registro->test_entrada)) {
                    return response()->json([
                        'status' => 409,
                        'message' => 'El test de entrada ya fue registrado anteriormente.'
                    ]);
                }

                $registro->update([
                    'test_entrada' => $request->test_entrada,
                    'fecha_te'     => now(),
                ]);

                return response()->json([
                    'status' => 200,
                    'message' => 'Test de entrada registrado correctamente.'
                ]);
            }

            /*
        |--------------------------------------------------------------------------
        | TEST DE SALIDA
        |--------------------------------------------------------------------------
        */

            if (!empty($registro->test_salida)) {
                return response()->json([
                    'status' => 409,
                    'message' => 'El test de salida ya fue registrado anteriormente.'
                ]);
            }

            $registro->update([
                'test_salida'   => $request->test_salida,
                'caso_practico' => $request->caso_practico,
                'ratings'       => $request->ratings,
                'sugerencias'   => $request->sugerencias,
                'fecha_ts'      => now(),
            ]);

            $realizoTestEntrada = !empty($registro->test_entrada);

            return response()->json([
                'status' => 200,
                'message' => 'Test de salida registrado correctamente.',
                'test_entrada_realizado' => $realizoTestEntrada,
                'mensaje_test_entrada' => $realizoTestEntrada
                    ? 'si_realizo'
                    : 'no_realizo'
            ]);
        } catch (\Exception $e) {

            Log::error('savePublicTest: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error inesperado.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function iWantMyCertificate(Request $request)
    {
        $request->validate([
            'slug'          => 'required|string',
            'email'         => 'required|email',
            'idempresario'  => 'required|integer',
            'c_constancia'  => 'required|boolean',
        ]);

        try {

            DB::beginTransaction();

            // Buscar el registro de la actividad
            $registro = EmpresarioActividad::where('slug', $request->slug)
                ->where('empresario_id', $request->idempresario)
                ->first();

            if (!$registro) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No se encontró el registro de la actividad.'
                ], 404);
            }

            // Actualizar si desea constancia
            $registro->update([
                'c_constancia' => $request->c_constancia ? 1 : 0
            ]);

            // Actualizar correo del empresario
            Empresario::where('id', $request->idempresario)
                ->update([
                    'correo_electronico' => $request->email
                ]);

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'La información se actualizó correctamente.'
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('iWantMyCertificate: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error inesperado.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
