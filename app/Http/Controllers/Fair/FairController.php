<?php

namespace App\Http\Controllers\Fair;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateFairRequest;
use Illuminate\Http\Request;
use App\Models\Fair;
use App\Models\Mype;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Mail\FeriasEmpresarialesMail;
use App\Models\FairPostulate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FairController extends Controller
{

    public function cyberWowList(Request $request)
    {
        $filters = [
            'year'      =>  $request->input('year'),
            'startDate' =>  $request->input('dateStart'),
            'endDate'   =>  $request->input('dateEnd'),
            'name'      =>  $request->input('name'),
            'orderby'   =>  $request->input('orderby'),
        ];

        $query = Fair::query();

        $query->where('fairtype_id', 2);

        $query->withItems($filters);

        $items = $query->paginate(100)->through(function ($item) {
            return $this->mapItems($item);
        });

        return response()->json([
            'data'   => $items,
            'status' => 200
        ]);
    }

    public function mujerProduceList(Request $request)
    {
        $filters = [
            'year'      =>  $request->input('year'),
            'startDate' =>  $request->input('dateStart'),
            'endDate'   =>  $request->input('dateEnd'),
            'name'      =>  $request->input('name'),
            'orderby'   =>  $request->input('orderby'),
        ];

        $query = Fair::query();

        // $query->where('fairtype_id', 1);

        $query = Fair::query()
            ->whereIn('fairtype_id', [3]) // ✅ Mujer produce
            ->withItems($filters);

        // $query->withItems($filters);

        $items = $query->paginate(150)->through(function ($item) {
            return $this->mapItems($item);
        });

        return response()->json([
            'data'   => $items,
            'status' => 200
        ]);
    }

    public function sedList(Request $request)
    {
        $filters = [
            'year'      =>  $request->input('year'),
            'startDate' =>  $request->input('dateStart'),
            'endDate'   =>  $request->input('dateEnd'),
            'name'      =>  $request->input('name'),
            'orderby'   =>  $request->input('orderby'),
        ];

        $query = Fair::query();

        // $query->where('fairtype_id', 1);

        $query = Fair::query()
            ->whereIn('fairtype_id', [1, 5]) // ✅ Incluye ambos tipos
            ->withItems($filters);

        // $query->withItems($filters);

        $items = $query->paginate(150)->through(function ($item) {
            return $this->mapItems($item);
        });

        return response()->json([
            'data'   => $items,
            'status' => 200
        ]);
    }

    private function mapItems($item)
    {
        return [
            'id' => $item->id,
            'slug' => $item->slug ?? null,
            'title' => $item->title ?? null,
            'subTitle' => $item->subTitle ?? null,
            'description' => $item->description ? $item->description : null,
            'description3'   => isset($item->description)
                ? (mb_strlen(strip_tags($item->description)) > 200
                    ? mb_substr(strip_tags($item->description), 0, 200) . '...'
                    : strip_tags($item->description))
                : null,
            'fairtype_id' => $item->fairType->id ?? null,
            'fairtype_name' => $item->fairType->name ?? null,
            'modality_id' => $item->modality->id ?? null,
            'modality_name' => $item->modality->name ?? null,
            'startDate' => $item->startDate ?? null,
            'endDate' => $item->endDate ?? null,
            'dateStartFormat' => $item->startDate ? Carbon::parse($item->startDate)->format('d/m/Y') : null,
            'dateEndFormat' => $item->endDate ? Carbon::parse($item->endDate)->format('d/m/Y') : null,
            'registered' => $item->postulantes_count,
            'registered_wow' => $item->postulantes_wow_count,

            'metaMypes' => $item->metaMypes ?? null,
            'city_id' => $item->region->id ?? null,
            'fecha_dashboard' => $item->fecha ? Carbon::parse($item->fecha)->format('d/m/Y') : null,
            'fecha' => $item->fecha,
            'city_name' => $item->region->name ?? null,
            'place' => $item->place ?? null,
            'hours' => $item->hours ?? null,
            'msgEndForm' => $item->msgEndForm ? $item->msgEndForm : null,
            'msgEndForm3' => isset($item->msgEndForm)
                ? (mb_strlen(strip_tags($item->msgEndForm)) > 200
                    ? mb_substr(strip_tags($item->msgEndForm), 0, 200) . '...'
                    : strip_tags($item->msgEndForm))
                : null,
            'msgSendEmail' => $item->msgSendEmail ? $item->msgSendEmail : null,
            'msgSendEmail3' => isset($item->msgSendEmail)
                ? (mb_strlen(strip_tags($item->msgSendEmail)) > 200
                    ? mb_substr(strip_tags($item->msgSendEmail), 0, 200) . '...'
                    : strip_tags($item->msgSendEmail))
                : null,
            'image' => $item->image ? [
                'id'   => $item->image->id ?? null,
                'name' => $item->image->name ?? null,
                'url'  => $item->image->url ? url($item->image->url) : null,
            ] : [
                'id'   => null,
                'name' => null,
                'url'  => null,
            ],
        ];
    }

    public function create(CreateFairRequest $request)
    {
        try {

            $user_role = getUserRole();
            $user_id = $user_role['user_id'];

            $data = $request->validated(); // Solo campos permitidos y validados

            // Generar slug único
            $slug = Str::slug($data['title']);
            $originalSlug = $slug;
            $count = 1;

            while (Fair::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $count;
                $count++;
            }

            $data['slug'] = $slug;
            $data['created_by'] = $user_id;
            $data['updated_by'] = $user_id;

            $fair = Fair::create($data);

            return response()->json([
                'data' => $fair,
                'message' => 'Feria creada con éxito',
                'status' => 200
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al crear feria: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Ocurrió un error al crear la feria',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function sedDetailsEvent($slug)
    {
        try {
            $today = Carbon::now();

            // $fair = Fair::where('slug', $slug)->where('fairtype_id', 1)->first();
            $fair = Fair::where('slug', $slug)
                ->whereIn('fairtype_id', [1, 5, 3])
                ->firstOrFail();

            if ($fair) {
                if ($today->gt(Carbon::parse($fair->endDate)->endOfDay())) {
                    return response()->json([
                        'data' => [
                            'title' => '¡Evento Finalizado!',
                            'message' => '
                            El evento que estabas buscando ya ha caducado o no se encuentra disponible en este momento. </br>
                            Pero no te detengas 🚀, </br>
                            nuevas oportunidades están en camino.</br>
                            Sigue atento(a) a nuestros próximos talleres, capacitaciones y eventos </br>
                            para seguir fortaleciendo tu emprendimiento.
                            ',
                            'status' => 404
                        ]
                    ]);
                }

                return response()->json([
                    'data' => [
                        'slug' => $fair->slug,
                        'title' => $fair->title,
                        'subTitle' => $fair->subTitle,
                        'description' => $fair->description,
                        'modality' => $fair->modality,
                        'typeFair' => $fair->fairtype_id,
                        'fecha' => $fair->fecha,
                        'place' => $fair->place,
                        'schedule' => $fair->hours
                    ],
                    'status' => 200
                ]);
            }

            return response()->json([
                'data' => [
                    'title' => 'No se encontró el evento.',
                    'message' => 'No existe una feria con este registro.',
                    'status' => 404
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los detalles del evento.',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }


    public function showEventCount($slug)
    {
        try {
            $fair = Fair::where('slug', $slug)->first();

            if (!$fair) {
                return response()->json([
                    'message' => 'Feria no encontrada.',
                    'status' => 404
                ]);
            }

            return response()->json([
                'data' => [
                    'total'      => $fair->postulantes()->count(),
                    'amountNow' => $fair->postulantes()->whereNotNull('attended')->count(),
                ],
                'status' => 200
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al obtener los datos.',
                'error'   => $e->getMessage(),
                'status'  => 500
            ]);
        }
    }



    public function update(Request $request, string $id)
    {
        try {
            $user_role = getUserRole();
            $role_array = $user_role['role_id'];

            // Validar roles autorizados
            // if (!in_array(5, $role_array) && !in_array(10, $role_array)) {
            //     return response()->json(['message' => 'Sin acceso', 'status' => 403], 403);
            // }

            // Encuentra el registro por su ID
            $registro = Fair::findOrFail($id);

            // Toma todos los datos enviados en el request
            $data = $request->all();

            // Asegurarse de que el slug no se modifique aunque venga en el request
            unset($data['slug']);

            // Si image_id NO está en los datos, lo quitamos del array para que no se actualice
            if (array_key_exists('image_id', $data) && is_null($data['image_id'])) {
                unset($data['image_id']);
            }

            // Actualiza el registro
            $registro->update($data);

            return response()->json(['message' => 'Registro actualizado con éxito', 'status' => 200]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Registro no encontrado', 'status' => 404], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar el registro', 'error' => $e->getMessage(), 'status' => 500], 500);
        }
    }




    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function updateFieldsMypeFair(Request $request, $ruc)
    {
        $mype = Mype::where('ruc', $ruc)->first();

        if ($mype) {
            $mype->hasPos = $request->input('hasPos');
            $mype->hasYape = $request->input('hasYape');
            $mype->hasVistualStore = $request->input('hasVistualStore');
            $mype->isFormalizedPnte = $request->input('isFormalizedPnte');
            $mype->hasElectronicInvoice = $request->input('hasElectronicInvoice');
            $mype->hasDelivery = $request->input('hasDelivery');
            $mype->isIndecopi = $request->input('isIndecopi');
            $mype->hasParticipatedProduce = $request->input('hasParticipatedProduce');
            $mype->nameService = $request->input('nameService');
            $mype->hasParticipatedFair = $request->input('hasParticipatedFair');
            $mype->nameFair = $request->input('nameFair');

            // Save the updated Mype record
            $mype->save();

            return response()->json(['success' => true, 'message' => 'Fields updated successfully.']);
        } else {
            return response()->json(['success' => false, 'message' => 'Mype not found.'], 404);
        }
    }

    public function postulateFair(Request $request)
    {
        $slug = $request->input('slug');

        $fair = Fair::where('slug', $slug)->first();

        if ($fair) {

            $exists = FairPostulate::where([
                'fair_id' => $fair->id,
                'mype_id' => $request->mype_id,
                'person_id' => $request->person_id,
            ])->exists();

            if ($exists) {
                return response()->json(['message' => 'Ya estás registrado', 'status' => 409]); // 409 Conflict
            }

            $data = [
                'fair_id' => $fair['id'],
                'mype_id' => $request->mype_id,
                'person_id' => $request->person_id,
                'ruc' => $request->ruc,
                'dni' => $request->dni,
                'email' => $request->email,
                // 'hasParticipatedProduce' => $request->hasParticipatedProduce,
                'nameService' => $request->nameService,
                // 'hasParticipatedFair' => $request->hasParticipatedFair,
                'nameFair' => $request->nameFair,
                'propagandamedia_id' => $request->propagandamedia_id
            ];

            FairPostulate::create($data);



            $mailer = 'digitalization';


            Mail::mailer($mailer)->to($request->input('email'))->send(new FeriasEmpresarialesMail($fair));



            // Mail::to($request->email)->send(new FeriasEmpresarialesMail($fair));




            return response()->json(['message' => 'Se le ha enviado un mensaje a su correo', 'status' => 200]);
        } else {

            return response()->json(['error' => 'Fair not found'], 404);
        }
    }


    public function fairApplicants(Request $request, $slugFair)
    {
        $search = $request->input('search');

        $fair = Fair::where('slug', $slugFair)->first();

        if (!$fair) {
            return response()->json(['message' => 'Fair not found'], 404);
        }

        $query = FairPostulate::with([
            'fair',
            'mype',
            'mype.region:id,name',
            'mype.province:id,name',
            'mype.district:id,name',
            'mype.category:id,name',
            'person',
            'person.pais:id,name',
            'person.city:id,name',
            'person.province:id,name',
            'person.district:id,name',
            'person.typedocument:id,name',
            'person.gender:id,name'
        ])
            ->where('fair_id', $fair->id)
            ->search($search)
            ->orderBy('created_at', 'desc');

        $data = $query->paginate(50);

        $mediaOptions = [
            1 => 'FACEBOOK',
            5 => 'CENTRO DE DESARROLLO (CDE)',
            6 => 'CAPACITACIONES',
            7 => 'INSTAGRAM',
            8 => 'GRUPOS DE WHATSAPP',
        ];

        $data->getCollection()->transform(function ($item) use ($mediaOptions) {
            return [
                'id' => $item->id,
                'fair_name' => $item->fair->title,
                'status' => $item->status,
                'email_send' => $item->email,
                'created_at' => $item->created_at,
                'ruc' => $item->mype->ruc,
                'comercialName' => $item->mype->comercialName,
                'socialReason' => $item->mype->socialReason,
                'businessSector' => $item->mype->category->name,
                'percentageOwnPlan' => $item->mype->percentageOwnPlan,
                'percentageMaquila' => $item->mype->percentageMaquila,
                'capacityProdMounth' => $item->mype->capacityProdMounth,
                'isGremio' => $item->mype->isGremio,
                'nameGremio' => $item->mype->nameGremio,
                'pointSale' => $item->mype->pointSale,
                'numberPointSale' => $item->mype->numberPointSale ?? null,
                'actividadEconomica' => $item->mype->actividadEconomica ?? null,
                'mype_city' => $item->mype->region->name ?? null,
                'mype_province' => $item->mype->province->name ?? null,
                'mype_district' => $item->mype->district->name ?? null,
                'mype_address' => $item->mype->address ?? null,
                'web' => $item->mype->web,
                'facebook' => $item->mype->facebook,
                'instagram' => $item->mype->instagram,
                'description' => $item->mype->description,
                'filePDF_name' => $item->mype->filePDF_name,
                'filePDF_url' => $item->mype->filePDF_path ? asset($item->mype->filePDF_path) : null,

                'logo_name' => $item->mype->logo_name,
                'logo_url' => $item->mype->logo_path ? asset($item->mype->logo_path) : null,
                'img1_name' => $item->mype->img1_name,
                'img1_url' => $item->mype->img1_path ? asset($item->mype->img1_path) : null,
                'img2_name' => $item->mype->img2_name,
                'img2_url' => $item->mype->img2_path ? asset($item->mype->img2_path) : null,
                'img3_name' => $item->mype->img3_name,
                'img3_url' => $item->mype->img3_path ? asset($item->mype->img3_path) : null,

                'documentnumber' => $item->person->documentnumber,
                'lastname' => $item->person->lastname . ' ' . $item->person->middlename,
                // 'middlename' => $item->person->middlename,
                'name' => $item->person->name,
                'phone' => $item->person->phone,
                'email' => $item->person->email,
                'birthdate' => $item->person->birthday,
                'sick' => $item->person->sick,
                'user_country' => $item->person->pais->name ?? null,
                'user_city' => $item->person->city->name ?? null,
                'user_province' => $item->person->province->name ?? null,
                'user_district' => $item->person->district->name ?? null,
                'address' => $item->person->address,
                'typedocument' => $item->person->typedocument->name ?? null,
                'gender' => $item->person->gender->name ?? null,

                'propagandamedia' => $mediaOptions[$item->propagandamedia_id] ?? ' ',


            ];
        });

        return response()->json(['data' => $data]);
    }

    public function toggleStatus($id)
    {

        $user_role = getUserRole();

        $role_array = $user_role['role_id'];

        if (
            in_array(5, $role_array) ||
            in_array(10, $role_array)
        ) {
            $fairPostulate = FairPostulate::find($id);

            if (!$fairPostulate) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            $fairPostulate->status = $fairPostulate->status == 0 ? 1 : 0;
            $fairPostulate->save();

            $message = $fairPostulate->status == 1
                ? 'Empresa aprobada para la feria'
                : 'Esta empresa no participará de la feria';

            return response()->json(['message' => $message, 'status' => 200]);
        } else {
            return response()->json(['message' => 'Sin acceso', 'status' => 500]);
        }
    }

    public function destroyParticipant($id)
    {
        $user_role = getUserRole();

        $role_array = $user_role['role_id'];

        if (
            in_array(5, $role_array) ||
            in_array(10, $role_array)
        ) {

            $fairPostulate = FairPostulate::find($id);

            if ($fairPostulate) {
                $fairPostulate->delete();
                return response()->json(['message' => 'Participante eliminado', 'status' => 200]);
            }

            return response()->json(['message' => 'Participante no encontrado'], 404);
        } else {
            return response()->json(['message' => 'Sin acceso', 'status' => 500]);
        }
    }

    public function messageFormCompleted($slug)
    {
        try {
            $fair = Fair::where('slug', $slug)->firstOrFail();

            return response()->json([
                'message' => $fair->msgEndForm,
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener el mensaje del formulario.',
                'error' => $e->getMessage(),
                'status' => 500
            ]);
        }
    }
}
