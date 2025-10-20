<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use App\Models\CyberwowBrand;
use App\Models\CyberwowLeader;
use App\Models\CyberwowOffer;
use App\Models\CyberwowParticipant;
use App\Models\Fair;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class CyberWowController extends Controller
{
    public function cyberwowBackStep1($idParticipante)
    {
        try {
            // Buscar el participante por ID
            $participant = CyberwowParticipant::findOrFail($idParticipante);

            // Actualizar los campos paso1 y paso2 a null
            $participant->update([
                'paso1' => null,
                'paso2' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Los pasos fueron reiniciados correctamente.',
                'data' => $participant,
                'status' => 200
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reiniciar los pasos: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cyberwowBackStep2($idParticipante)
    {
        try {
            $participant = CyberwowParticipant::findOrFail($idParticipante);

            $participant->paso2 = null;
            $participant->paso3 = null;
            $participant->save();

            return response()->json([
                'success' => true,
                'message' => 'Los pasos fueron reiniciados correctamente go 2',
                'status' => 200
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reiniciar los pasos: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function cyberwowDataStep2($idEvent, $idParticipante)
    {
        try {
            // 1️⃣ Buscar el participante
            $participante = CyberwowParticipant::find($idParticipante);

            if (!$participante) {
                return response()->json([
                    'success' => false,
                    'message' => 'Participante no encontrado.'
                ], 404);
            }

            // 2️⃣ Buscar la marca asociada al evento, usuario autenticado y participante
            $brand = CyberwowBrand::with(['logo256', 'logo160'])
                ->where('wow_id', $idEvent)
                ->where('user_id', Auth::id())
                ->where('company_id', $participante->id)
                ->first();

            if (!$brand) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró información de la marca para este participante.',
                    'status' => 404
                ]);
            }

            // 3️⃣ Armar los datos con las relaciones
            $data = [
                'isService'    => $brand->isService,
                'description'  => $brand->description,
                'url'          => $brand->url,
                'logo256_url'  => $brand->logo256?->url,
                'logo160_url'  => $brand->logo160?->url,
                'logo256_id'  => $brand->logo256?->id,
                'logo160_id'  => $brand->logo160?->id,
                'wow_id'       => $brand->wow_id,
                'user_id'      => $brand->user_id,
                'company_id'   => $brand->company_id,
                'red'          => $brand->red,
                'participante' => [
                    'id'          => $participante->id,
                    'name'        => $participante->name ?? null,
                    'company_id'  => $participante->company_id,
                ],
            ];

            // 4️⃣ Retornar respuesta
            return response()->json([
                'success' => true,
                'data'    => $data,
                'status'  => 200
            ], 200);
        } catch (\Exception $e) {
            // 5️⃣ Manejo de errores genérico
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la información.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function cyberwowDataStep3($slug, $company_id)
    {
        try {
            // Buscar la feria por slug
            $fair = Fair::where('slug', $slug)->first();

            if (!$fair) {
                return response()->json([
                    'success' => false,
                    'message' => 'Feria no encontrada.',
                ], 404);
            }

            // Obtener las ofertas por empresa y evento
            $offers = CyberwowOffer::with(['imageFull', 'imagePhone'])
                ->where('wow_id', $fair->id)
                ->where('company_id', $company_id)
                ->orderBy('dia')
                ->get();

            // Verificar si existen ofertas
            if ($offers->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No hay ofertas registradas aún para esta empresa.',
                    'offers' => [],
                    'status' => 204
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Ofertas obtenidas correctamente.',
                'offers' => $offers,
                'status' => 200
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las ofertas.',
                'error' => $th->getMessage()
            ], 500);
        }
    }


    function updateLeadertoSupervisor(Request $request)
    {
        try {

            $slug = $request->input('slug');
            $supervisor = $request->input('supervisor');
            $user_id = $request->input('user_id');

            $fair = Fair::where('slug', $slug)->firstOrFail();

            $wow_id = $fair->id;

            CyberwowLeader::where('wow_id', $wow_id)
                ->where('user_id', $user_id)
                ->update(['supervisor' => $supervisor]);

            return response()->json([
                'success' => true,
                'message' => 'Supervisor actualizado correctamente.',
                'status' => 200
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el supervisor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getListLeadersToSupervisor(Request $request)
    {
        try {
            $slug = $request->input('slug');
            $supervisor = $request->input('supervisor');

            $fair = Fair::where('slug', $slug)->first();

            if (!$fair) {
                return response()->json([
                    'success' => false,
                    'message' => 'Feria no encontrada.',
                ], 404);
            }

            $leaders = CyberwowLeader::where('wow_id', $fair->id)
                ->where('supervisor', $supervisor)
                ->with('user:id,name,lastname')
                ->get();

            $formatted = $leaders->map(function ($leader) {
                $fullName = "{$leader->user->name} {$leader->user->lastname}";
                return [
                    'label' => mb_strtoupper($fullName, 'UTF-8'),
                    'value' => $leader->user_id,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formatted,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los líderes.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function followUpLeaderToCompany(Request $request)
    {
        try {
            $slug = $request->input('slug');
            $userId = $request->input('user_id');

            // 1️⃣ Buscar la feria
            $fair = Fair::where('slug', $slug)->first();

            if (!$fair) {
                return response()->json([
                    'success' => false,
                    'message' => 'Feria no encontrada.',
                ], 404);
            }

            // 2️⃣ Buscar los participantes de ese user y feria
            $participants = CyberwowParticipant::with(['user'])
                ->where('event_id', $fair->id)
                ->where('user_id', $userId)
                ->get();

            // 3️⃣ Transformar datos
            $data = $participants->map(function ($item) {

                // 🟢🟡🔴 Semáforo de pasos
                $paso1 = $item->paso1;
                $paso2 = $item->paso2;
                $paso3 = $item->paso3;

                if ($paso1 == 1 && $paso2 == 1 && $paso3 == 1) {
                    $estado = 'green';
                    $estadoName = 'Ofertas';
                } elseif ($paso1 == 1 && $paso2 == 1) {
                    $estado = '#ffa700';
                    $estadoName = 'Marca';
                } else {
                    $estado = '#ff626a';
                    $estadoName = 'Validación';
                }

                return [
                    'id' => $item->id,
                    'nombre' => $item->nombreComercial,
                    'razonSocial' => $item->razonSocial,
                    'ruc' => $item->ruc ?? 'Sin RUC',
                    'representante' => $item->name . ' ' . $item->lastname,
                    'dni' => $item->documentnumber,
                    'celular' => $item->phone,
                    'asesor' => $item->user ? "{$item->user->name} {$item->user->lastname}" : 'No asignado',
                    'estado' => $estado,
                    'estadoName' => $estadoName
                ];
            });

            // 4️⃣ Retornar respuesta
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las empresas.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function followUpLeaderToBrand(Request $request)
    {
        try {
            $slug = $request->input('slug');
            $companyId = $request->input('company_id');

            // 1️⃣ Buscar la feria por el slug
            $fair = Fair::where('slug', $slug)->first();

            if (!$fair) {
                return response()->json([
                    'success' => false,
                    'message' => 'Feria no encontrada.',
                ], 404);
            }

            // 2️⃣ Buscar una sola marca que coincida con wow_id y company_id
            $brand = CyberwowBrand::where('wow_id', $fair->id)
                ->where('company_id', $companyId)
                ->first();

            if (!$brand) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró una marca para esta empresa.',
                    'data' => null,
                ]);
            }

            // 3️⃣ Obtener el logo (si existe)
            $image = $brand->logo160_id ? Image::find($brand->logo160_id) : null;

            // 4️⃣ Preparar la respuesta
            $data = [
                'id' => $brand->id,
                'isService' => $brand->isService,
                'description' => $brand->description,
                'url' => $brand->url,
                'logo' => $image ? [
                    'id' => $image->id,
                    'name' => $image->name,
                    'url' => $image->url,
                ] : null,
            ];

            // 5️⃣ Retornar resultado
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la marca.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function followUpLeaderToProducts(Request $request)
    {
        try {
            $slug = $request->input('slug');
            $companyId = $request->input('company_id');

            // 1️⃣ Buscar la feria por el slug
            $fair = Fair::where('slug', $slug)->first();

            if (!$fair) {
                return response()->json([
                    'success' => false,
                    'message' => 'Feria no encontrada.',
                    'data' => [],
                ], 404);
            }

            // 2️⃣ Buscar las ofertas que coincidan con wow_id y company_id
            $offers = CyberwowOffer::where('wow_id', $fair->id)
                ->where('company_id', $companyId)
                ->get();

            // 3️⃣ Mapear resultados con la estructura esperada
            $productos = $offers->map(function ($offer) {
                $image = $offer->imgFull
                    ? Image::find($offer->imgFull)
                    : ($offer->img ? Image::find($offer->img) : null);

                return [
                    'nombre'         => $offer->title,
                    'categoria'      => $offer->category,
                    'tipo'           => $offer->tipo,
                    'precioOriginal' => $offer->precioAnterior,
                    'precioActual'   => $offer->precioOferta,
                    'imagen'         => $image ? $image->url : null,
                    'link'           => $offer->link,
                ];
            });

            // 4️⃣ Retornar siempre un array, aunque esté vacío
            return response()->json([
                'success' => true,
                'data' => $productos->toArray(), // Si no hay, devuelve []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los productos.',
                'error' => $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    public function removeLeaderFromCompany($idParticipante)
    {
        // Buscar al participante por ID
        $participant = CyberwowParticipant::find($idParticipante);

        // Validar que exista
        if (!$participant) {
            return response()->json([
                'success' => false,
                'message' => 'Participante no encontrado.'
            ], 404);
        }

        // Establecer el user_id en null
        $participant->user_id = null;

        // Guardar cambios
        $participant->save();

        return response()->json([
            'success' => true,
            'message' => 'Líder eliminado correctamente de la empresa.'
        ]);
    }
}
