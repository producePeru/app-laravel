<?php

namespace App\Http\Controllers\Historial;

use App\Http\Controllers\Controller;
use App\Models\Advisory;
use App\Models\Formalization10;
use App\Models\Formalization20;
use App\Models\People;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception;

class FollowUpController extends Controller
{
    public function registrationDay($dni)
    {
        try {
            $person = People::with(['user.cde', 'userUpdated.cde'])
                ->where('documentnumber', $dni)
                ->first();

            if (!$person) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró a la persona con ese DNI',
                    'status' => 404
                ]);
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => [
                    'registeredby' => $person->user
                        ? trim($person->user->name . ' ' . $person->user->lastname . ' ' . $person->user->middlename)
                        : null,

                    'updatedby' => $person->userUpdated
                        ? trim($person->userUpdated->name . ' ' . $person->userUpdated->lastname . ' ' . $person->userUpdated->middlename)
                        : null,

                    'cde' => $person->user && $person->user->cde
                        ? $person->user->cde->name
                        : null,

                    'cdeUpdate' => $person->userUpdated && $person->userUpdated->cde
                        ? $person->userUpdated->cde->name
                        : null,

                    'date' => Carbon::parse($person->created_at)
                        ->locale('es')
                        ->timezone('America/Lima')
                        ->translatedFormat('d \\d\\e F \\d\\e Y H:i'),
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al obtener la información',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function showAllConsultancies($idPeople)
    {
        try {
            // Validar ID
            if (!is_numeric($idPeople)) {
                return response()->json([
                    'message' => 'El ID proporcionado no es válido',
                    'status'  => 400
                ], 400);
            }

            // Consultar en Advisory por people_id
            $consultancies = Advisory::with([
                'economicsector:id,name',
                'comercialactivity:id,name',
                'component:id,name',
                'theme:id,name',
                'modality:id,name',
                'city:id,name',
                'province:id,name',
                'district:id,name',
                'asesor.cde'
            ])
                ->where('people_id', $idPeople)
                ->orderBy('created_at', 'desc')
                ->get();

            // Si no hay resultados
            if ($consultancies->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron consultorías para esta persona',
                    'status'  => 404
                ]);
            }

            $data = $consultancies->map(function ($item) {
                return [
                    'id'                => $item->id,
                    'sector_economico'  => $item->economicsector->name ?? null,
                    'actividad'         => $item->comercialactivity->name ?? null,
                    'componente'        => $item->component->name ?? null,
                    'tema'              => $item->theme->name ?? null,
                    'modalidad'         => $item->modality->name ?? null,
                    'region'            => $item->city->name ?? null,
                    'provincia'         => $item->province->name ?? null,
                    'distrito'          => $item->district->name ?? null,
                    'ruc'               => $item->ruc,
                    'observaciones'     => $item->observations,
                    'asesor'            => ($item->user->name . ' ' . $item->user->lastname . ' ' . $item->user->middlename) ?? null,
                    'cde'               => $item->user && $item->user->cde
                        ? $item->user->cde->name
                        : null,
                    'registro'          => Carbon::parse($item->created_at)
                        ->timezone('America/Lima')
                        ->format('d/m/Y H:i A'),
                ];
            });

            return response()->json([
                'message' => 'Consultorías encontradas',
                'status'  => 200,
                'data'    => $data
            ], 200);
        } catch (\Exception $e) {
            // Capturar errores inesperados
            return response()->json([
                'message' => 'Error al obtener las consultorías',
                'error'   => $e->getMessage(),
                'status'  => 500
            ], 500);
        }
    }


    public function showAllF10($idPeople)
    {
        try {
            // Validar ID
            if (!is_numeric($idPeople)) {
                return response()->json([
                    'message' => 'El ID proporcionado no es válido',
                    'status'  => 400
                ]);
            }

            $consultancies = Formalization10::with([
                'economicsector:id,name',
                'comercialactivity:id,name',
                'detailprocedure:id,name',
                'modality:id,name',
                'city:id,name',
                'province:id,name',
                'district:id,name',
                'asesor.cde'
            ])
                ->where('people_id', $idPeople)
                ->orderBy('created_at', 'desc')
                ->get();

            // Si no hay resultados
            if ($consultancies->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron consultorías para esta persona',
                    'status'  => 404
                ]);
            }

            $data = $consultancies->map(function ($item) {
                return [
                    'id'                => $item->id,
                    'sector_economico'  => $item->economicsector->name ?? null,
                    'actividad'         => $item->comercialactivity->name ?? null,
                    'detalle'           => $item->detailprocedure->name ?? null,
                    'modalidad'         => $item->modality->name ?? null,
                    'ruc'               => $item->ruc,
                    'region'            => $item->city->name ?? null,
                    'provincia'         => $item->province->name ?? null,
                    'distrito'          => $item->district->name ?? null,
                    'address'           => $item->address ?? null,
                    'asesor'            => ($item->user->name . ' ' . $item->user->lastname . ' ' . $item->user->middlename) ?? null,
                    'cde'               => $item->user && $item->user->cde
                        ? $item->user->cde->name
                        : null,
                    'registro'          => Carbon::parse($item->created_at)
                        ->timezone('America/Lima')
                        ->format('d/m/Y H:i A'),
                ];
            });

            return response()->json([
                'message' => 'Consultorías encontradas',
                'status'  => 200,
                'data'    => $data
            ], 200);
        } catch (\Exception $e) {
            // Capturar errores inesperados
            return response()->json([
                'message' => 'Error al obtener las consultorías',
                'error'   => $e->getMessage(),
                'status'  => 500
            ], 500);
        }
    }

    public function showAllF20($idPeople)
    {
        try {
            // Validar ID
            if (!is_numeric($idPeople)) {
                return response()->json([
                    'message' => 'El ID proporcionado no es válido',
                    'status'  => 400
                ], 400);
            }

            $consultancies = Formalization20::with([
                'regime:id,name',
                'city:id,name',
                'province:id,name',
                'district:id,name',
                'modality:id,name',
                'economicsector:id,name',
                'comercialactivity:id,name',
                'notary:id,name',
                'typecapital:id,name',
                'asesor.cde'
            ])
                ->where('people_id', $idPeople)
                ->orderBy('created_at', 'desc')
                ->get();

            // Si no hay resultados
            if ($consultancies->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron consultorías para esta persona',
                    'status'  => 404
                ]);
            }

            $data = $consultancies->map(function ($item) {
                return [
                    'id'                => $item->id,
                    'ruc'               => $item->ruc,
                    'typeregimen'       => $item->regime->name ?? null,
                    'nameEmpresa'       => $item->nameMype ?? null,
                    'region'            => $item->city->name ?? null,
                    'provincia'         => $item->province->name ?? null,
                    'distrito'          => $item->district->name ?? null,
                    'address'           => $item->address ?? null,
                    'modalidad'         => $item->modality->name ?? null,
                    'sector_economico'  => $item->economicsector->name ?? null,
                    'actividad'         => $item->comercialactivity->name ?? null,
                    'numsolicitud'      => $item->numbernotary ?? null,
                    'notaria'           => $item->notary->name ?? null,
                    'fecharecepcion'    => Carbon::parse($item->dateReception)
                        ->timezone('America/Lima')
                        ->format('d/m/Y'),
                    'fechasid'          => Carbon::parse($item->datetramite)
                        ->timezone('America/Lima')
                        ->format('d/m/Y'),
                    'isbic'             => $item->isbic ?? null,
                    'montocapital'      => $item->montocapital ?? null,
                    'typecapital'       => $item->typecapital->name ?? null,
                    'asesor'            => ($item->user->name . ' ' . $item->user->lastname . ' ' . $item->user->middlename) ?? null,
                    'cde'               => $item->user && $item->user->cde
                        ? $item->user->cde->name
                        : null,
                    'registro'          => Carbon::parse($item->created_at)
                        ->timezone('America/Lima')
                        ->format('d/m/Y H:i A'),
                ];
            });

            return response()->json([
                'message' => 'Consultorías encontradas',
                'status'  => 200,
                'data'    => $data
            ], 200);
        } catch (\Exception $e) {
            // Capturar errores inesperados
            return response()->json([
                'message' => 'Error al obtener las consultorías',
                'error'   => $e->getMessage(),
                'status'  => 500
            ], 500);
        }
    }
}
