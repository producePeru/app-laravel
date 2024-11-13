<?php

namespace App\Http\Controllers\Fair;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Fair;
use App\Models\Mype;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Mail\FeriasEmpresarialesMail;
use App\Models\FairPostulate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class FairController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $query = Fair::with([
            'fairPostulate',
            'region',
            'provincia',
            'distrito',
            'fairType',
            'profile:id,user_id,name,lastname,middlename'
        ])
            ->withCount('fairPostulate')
            ->search($search)
            ->orderBy('created_at', 'desc');


        $data = $query->paginate(50);


        $data->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'slug' => $item->slug,
                'title' => $item->title,
                'subTitle' => $item->subTitle,
                'description' => $item->description,
                'metaMypes' => $item->metaMypes,
                'countMypes' => $item->fair_postulate_count,
                'metaSales' => $item->metaSales,
                'startDate' => Carbon::parse($item->startDate)->format('d-m-Y'),
                'endDate' => Carbon::parse($item->endDate)->format('d-m-Y'),
                'startDate2' => $item->startDate,
                'endDate2' => $item->endDate,
                'fairtype_id' => $item->fairType ? $item->fairType->id : null,
                'powerBy' => $item->powerBy,
                'modality' => $item->modality,
                'city' => $item->region->name,
                'province' => $item->provincia->name,
                'district' => $item->distrito->name,
                'city_id' => $item->region->id,
                'province_id' => $item->provincia->id,
                'district_id' => $item->distrito->id,
                'profile' => $item->profile->name . ' ' . $item->profile->lastname . ' ' . $item->profile->middlename,
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function create(Request $request)
    {
        $user_role = getUserRole();

        $role_array = $user_role['role_id'];

        if (
            in_array(5, $role_array) ||
            in_array(10, $role_array)
        ) {
            $user_role = getUserRole();
            $user_id = $user_role['user_id'];

            $data = $request->all();

            $slug = Str::slug($data['title']);

            $originalSlug = $slug;

            $count = 1;

            while (Fair::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $count;
                $count++;
            }

            $data['slug'] = $slug;
            $data['user_id'] = $user_id;

            Fair::create($data);

            return response()->json(['message' => 'Feria creada con éxito', 'status' => 200]);
        } else {
            return response()->json(['message' => 'Sin acceso', 'status' => 500]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    public function show($slug)
    {
        $today = Carbon::now();

        $fair = Fair::where('slug', $slug)->first();

        if ($fair) {

            if ($today->gt(Carbon::parse($fair->endDate)->endOfDay())) {
                return response()->json(['message' => 'La feria ya no está vigente.', 'status' => 500]);
            }

            return response()->json(['data' => [
                'slug' => $fair->slug,
                'title' => $fair->title,
                'subTitle' => $fair->subTitle,
                'description' => $fair->description,
                'modality' => $fair->modality
            ], 'status' => 200]);
        }

        return response()->json(['message' => 'Feria no encontrada.', 'status' => 400]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user_role = getUserRole();

        $role_array = $user_role['role_id'];

        if (
            in_array(5, $role_array) ||
            in_array(10, $role_array)
        ) {
            // Encuentra el registro por su ID
            $registro = Fair::findOrFail($id);

            // Toma todos los datos enviados en el request
            $data = $request->all();

            // Solo regenerar el slug si el título fue actualizado
            if (isset($data['title'])) {
                $slug = Str::slug($data['title']);
                $originalSlug = $slug;
                $count = 1;

                // Asegurarse de que el nuevo slug sea único
                while (Fair::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                    $slug = $originalSlug . '-' . $count;
                    $count++;
                }

                $data['slug'] = $slug;
            }

            // Actualizar los datos, incluido el slug si fue modificado
            $registro->update($data);

            return response()->json(['message' => 'Registro actualizado con éxito', 'status' => 200]);
        } else {
            return response()->json(['message' => 'Sin acceso', 'status' => 500]);
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
                'hasParticipatedProduce' => $request->hasParticipatedProduce,
                'nameService' => $request->nameService,
                'hasParticipatedFair' => $request->hasParticipatedFair,
                'nameFair' => $request->nameFair
            ];

            FairPostulate::create($data);

            Mail::to($request->email)->send(new FeriasEmpresarialesMail($fair));

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

        $data->getCollection()->transform(function ($item) {
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
                'numberPointSale' => $item->mype->numberPointSale,
                'actividadEconomica' => $item->mype->actividadEconomica,
                'mype_city' => $item->mype->region->name,
                'mype_province' => $item->mype->province->name,
                'mype_district' => $item->mype->district->name,
                'mype_address' => $item->mype->address,
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
                'user_country' => $item->person->pais->name,
                'user_city' => $item->person->city->name,
                'user_province' => $item->person->province->name,
                'user_district' => $item->person->district->name,
                'address' => $item->person->address,
                'typedocument' => $item->person->typedocument->name,
                'gender' => $item->person->gender->name,
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
}
