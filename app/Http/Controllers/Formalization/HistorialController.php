<?php

namespace App\Http\Controllers\Formalization;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Advisory;
use App\Models\Formalization10;
use App\Models\Formalization20;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class HistorialController extends Controller
{
    private function getUserRole()
    {
        $user_id = Auth::user()->id;

        $roleUser = DB::table('role_user')
            ->where('user_id', $user_id)
            ->first();

        if ($user_id != $roleUser->user_id) {
            return response()->json(['message' => 'Este rol no es correcto', 'status' => 404]);
        }

        return [
            "role_id" => $roleUser->role_id,
            'user_id' => $user_id
        ];
    }

    public function historialAdvisories()
    {
        $role_id = $this->getUserRole()['role_id'];
        $user_id = $this->getUserRole()['user_id'];

        // 1.supervisor
        if ($role_id === 1 || $user_id === 1) {
            $results = Advisory::withAllAdvisories();
            return response()->json($results, 200);
        }
        // 2.asesor
        if ($role_id === 2) {
            $results = Advisory::ByUserId($user_id)->withAllAdvisories();
            return response()->json($results, 200);
        }
    }

    public function historialFormalizations10()
    {
        $role_id = $this->getUserRole()['role_id'];
        $user_id = $this->getUserRole()['user_id'];

        // 1.supervisor
        if ($role_id === 1 || $user_id === 1) {
            $results = Formalization10::withAllFomalizations10();
            return response()->json($results, 200);
        }
        // 2. asesor
        if ($role_id === 2) {
            $results = Formalization10::ByUserId($user_id)->withAllFomalizations10();
            return response()->json($results, 200);
        }
    }

    public function historialFormalizations20()
    {
        $role_id = $this->getUserRole()['role_id'];
        $user_id = $this->getUserRole()['user_id'];

        // Supervisor y superandin
        if ($role_id === 1 || $user_id === 1) {
            $results = Formalization20::withAllFomalizations20();
            return response()->json($results, 200);
        }

        if ($role_id === 2) {
            $results = Formalization20::ByUserId($user_id)->withAllFomalizations20();
            return response()->json($results, 200);
        }
    }


    // ULTIMO 2025 ***
    public function filterHistorialAdvisoriesByDates(Request $request)
    {
        $permission = getPermission('reportes-asesoria-formalizacion');

        // if (!$permission['hasPermission']) {
        //     return response()->json([
        //         'message' => 'No tienes permiso para acceder a esta sección',
        //         'status' => 403
        //     ]);
        // }

        $filters = [
            'asesor'    => $request->input('asesor'),
            'name'      => $request->input('name'),
            'dateStart' => $request->input('dateStart'),
            'dateEnd'   => $request->input('dateEnd'),
            'year'      => $request->input('year'),
            'typeCdes'  => $request->input('typeCdes'),
        ];

        $user = Auth::user();

        $query = Advisory::query();


        if ($user->rol == 1) {
            $query->withAdvisoryRangeDate($filters);
        } else if ($user->rol == 2) {
            $query->withAdvisoryRangeDate($filters)->where('user_id', $user->id);
        } else {
            return response()->json([
                'message' => 'No tienes permiso para acceder a esta sección',
                'status' => 403
            ]);
        }

        $advisories = $query->paginate(90)->through(function ($advisory) {
            return $this->mapAdvisory($advisory);
        });

        return response()->json([
            'data'   => $advisories,
            'rol' => $user->rol,
            'status' => 200
        ]);
    }

    private function mapAdvisory($advisory)
    {
        return [
            'id'                    => $advisory->id,
            'date'                  => $advisory->created_at->format('d/m/Y h:i A'),
            'asesor'                => $advisory->user->name . ' ' . $advisory->user->lastname . ' ' . $advisory->user->middlename,

            // 'asesor_cde_city'       => $advisory->sede->city ?? null,
            // 'asesor_cde_province'   => $advisory->sede->province ?? null,
            // 'asesor_cde_district'   => $advisory->sede->district ?? null,
            // 'asesor_cde_id'         => $advisory->sede->cdetype_id ?? null,

            'asesor_cde'            => $advisory->sede ? strtoupper($advisory->sede->name) : null,

            'emp_document_type'     => $advisory->people->typedocument->avr ?? null,
            'emp_document_number'   => $advisory->people->documentnumber ?? null,
            'emp_country'           => isset($advisory->people->pais->name) ? strtoupper($advisory->people->pais->name) : 'PERU',
            'emp_birth'             => $advisory->people->birthday ? \Carbon\Carbon::parse($advisory->people->birthday)->format('d/m/Y') : null,
            'emp_age'               => $advisory->people->birthday ? \Carbon\Carbon::parse($advisory->people->birthday)->age : null,
            'emp_lastname'          => $advisory->people->lastname,
            'emp_middlename'        => $advisory->people->middlename,
            'emp_name'              => $advisory->people->name,
            'emp_gender'            => $advisory->people->gender->name == 'FEMENINO' ? 'F' : 'M',
            'emp_discapabilities'   => $advisory->people->sick ? strtoupper($advisory->people->sick) : null,
            'emp_soons'             => $advisory->people->hasSoon ?? null,
            'emp_phone'             => $advisory->people->phone,
            'emp_email'             => $advisory->people->email ? strtolower($advisory->people->email) : '-',

            'supervisor'            => isset($advisory->supervisor->supervisorUser->profile) ? strtoupper($advisory->supervisor->supervisorUser->profile->name . ' ' . $advisory->supervisor->supervisorUser->profile->lastname . ' ' . $advisory->supervisor->supervisorUser->profile->middlename) : null,

            'city'                  => $advisory->city->name ?? null,
            'province'              => $advisory->province->name ?? null,
            'district'              => $advisory->district->name ?? null,
            'ruc'                   => $advisory->ruc ?? null,
            'econimic_service'      => $advisory->economicsector->name ?? null,
            'activity_comercial'    => $advisory->comercialactivity->name ?? null,
            'component'             => $advisory->component->name ?? null,
            'theme'                 => strtoupper($advisory->theme->name) ?? null,
            'observations'          => $advisory->observations ?? null,
            'modality'              => $advisory->modality->name ?? null,

            // ids
            'econimic_sector_id'    => $advisory->economicsector->id ?? null,
            'activity_comercial_id' => $advisory->comercialactivity->id ?? null,
            'component_id'             => $advisory->component->id ?? null,
            'theme_id'              => $advisory->theme->id ?? null,
            'modality_id'           => $advisory->modality->id ?? null,
            'city_id'               => $advisory->city->id ?? null,
            'province_id'           => $advisory->province->id ?? null,
            'district_id'           => $advisory->district->id ?? null,
        ];
    }

    // 10
    public function filterHistorialFormalizations10ByDates(Request $request)
    {
        $permission = getPermission('asesorias-formalizaciones-reportes');

        // if (!$permission['hasPermission']) {
        //     return response()->json([
        //         'message' => 'No tienes permiso para acceder a esta sección',
        //         'status' => 403
        //     ]);
        // }

        $filters = [
            'asesor'    => $request->input('asesor'),
            'name'      => $request->input('name'),
            'dateStart' => $request->input('dateStart'),
            'dateEnd'   => $request->input('dateEnd'),
            'year'      => $request->input('year'),
            'typeCdes'  => $request->input('typeCdes'),
        ];

        $user = Auth::user();

        $query = Formalization10::query();

        if ($user->rol == 1) {
            $query->withFormalizationRangeDate($filters);
        } else if ($user->rol == 2) {
            $query->withFormalizationRangeDate($filters)->where('user_id', $user->id);
        } else {
            return response()->json([
                'message' => 'No tienes permiso para acceder a esta sección',
                'status' => 403
            ]);
        }

        $formalizations = $query->paginate(90)->through(function ($item) {
            return $this->mapFormalization10($item);
        });

        return response()->json([
            'data'   => $formalizations,
            'rol' => $user->rol,
            'status' => 200
        ]);
    }

    private function mapFormalization10($f10)
    {
        return [
            'id'                    => $f10->id,
            'date'                  => $f10->created_at->format('d/m/Y h:i A'),
            'asesor'                => $f10->user->name . ' ' . $f10->user->lastname . ' ' . $f10->user->middlename,
            'asesor_cde_city'       => $f10->sede->city ?? null,
            'asesor_cde_province'   => $f10->sede->province ?? null,
            'asesor_cde_district'   => $f10->sede->district ?? null,
            'asesor_cde'            => $f10->sede ? strtoupper($f10->sede->name) : null,

            'emp_document_type'     => $f10->people->typedocument->avr ?? null,
            'emp_document_number'   => $f10->people->documentnumber ?? null,
            'emp_country'           => isset($f10->people->pais->name) ? strtoupper($f10->people->pais->name) : 'PERU',
            'emp_birth'             => $f10->people->birthday ? \Carbon\Carbon::parse($f10->people->birthday)->format('d/m/Y') : null,
            'emp_age'               => $f10->people->birthday ? \Carbon\Carbon::parse($f10->people->birthday)->age : null,
            'emp_lastname'          => $f10->people->lastname,
            'emp_middlename'        => $f10->people->middlename,
            'emp_name'              => $f10->people->name,
            'emp_gender'            => $f10->people->gender->name == 'FEMENINO' ? 'F' : 'M',
            'emp_discapabilities'   => $f10->people->sick ? strtoupper($f10->people->sick) : null,
            'emp_soons'             => $f10->people->hasSoon ?? null,
            'emp_phone'             => $f10->people->phone,
            'emp_email'             => $f10->people->email ? strtolower($f10->people->email) : '-',

            'supervisor'            => isset($f10->supervisor->supervisorUser->profile) ? strtoupper($f10->supervisor->supervisorUser->profile->name . ' ' . $f10->supervisor->supervisorUser->profile->lastname . ' ' . $f10->supervisor->supervisorUser->profile->middlename) : null,

            'city'                  => $f10->city->name ?? null,
            'province'              => $f10->province->name ?? null,
            'district'              => $f10->district->name ?? null,
            'address'               => $f10->address ?? null,
            'ruc'                   => $f10->ruc ?? null,

            'econimic_sector'       => $f10->economicsector->name ?? null,
            'activity_comercial'    => $f10->comercialactivity->name ?? null,
            'detail_tramit'         => $f10->detailprocedure->name ?? null,
            'modality'              => $f10->modality->name ?? null,

            // ids
            'econimic_sector_id'    => $f10->economicsector->id ?? null,
            'activity_comercial_id' => $f10->comercialactivity->id ?? null,
            'detailprocedure_id'    => $f10->detailprocedure->id ?? null,
            'modality_id'           => $f10->modality->id ?? null,
            'city_id'               => $f10->city->id ?? null,
            'province_id'           => $f10->province->id ?? null,
            'district_id'           => $f10->district->id ?? null,
        ];
    }

    public function filterHistorialFormalizations20ByDates(Request $request)
    {
        $permission = getPermission('asesorias-formalizaciones-reportes');

        // if (!$permission['hasPermission']) {
        //     return response()->json([
        //         'message' => 'No tienes permiso para acceder a esta sección',
        //         'status' => 403
        //     ]);
        // }

        $filters = [
            'asesor'    => $request->input('asesor'),
            'name'      => $request->input('name'),
            'dateStart' => $request->input('dateStart'),
            'dateEnd'   => $request->input('dateEnd'),
            'year'      => $request->input('year'),
            'typeCdes'  => $request->input('typeCdes'),
        ];

        $user = Auth::user();

        $query = Formalization20::query();

        if ($user->rol == 1) {
            $query->withFormalizationRangeDate($filters);
        } else if ($user->rol == 2) {
            $query->withFormalizationRangeDate($filters)->where('user_id', $user->id);
        } else {
            return response()->json([
                'message' => 'No tienes permiso para acceder a esta sección',
                'status' => 403
            ]);
        }

        $formalizations = $query->paginate(90)->through(function ($item) {
            return $this->mapFormalization20($item);
        });

        return response()->json([
            'data'   => $formalizations,
            'rol' => $user->rol,
            'status' => 200
        ]);
    }

    private function mapFormalization20($f20)
    {
        return [
            'id'                    => $f20->id,
            'date'                  => $f20->created_at->format('d/m/Y h:i A'),
            'asesor'                => $f20->user->name . ' ' . $f20->user->lastname . ' ' . $f20->user->middlename,
            'asesor_cde_city'       => $f20->sede->city ?? null,
            'asesor_cde_province'   => $f20->sede->province ?? null,
            'asesor_cde_district'   => $f20->sede->district ?? null,
            'asesor_cde'            => $f20->sede ? strtoupper($f20->sede->name) : null,

            'emp_document_type'     => $f20->people->typedocument->avr ?? null,
            'emp_document_number'   => $f20->people->documentnumber ?? null,
            'emp_country'           => isset($f20->people->pais->name) ? strtoupper($f20->people->pais->name) : 'PERU',
            'emp_birth'             => $f20->people->birthday ? \Carbon\Carbon::parse($f20->people->birthday)->format('d/m/Y') : null,
            'emp_age'               => $f20->people->birthday ? \Carbon\Carbon::parse($f20->people->birthday)->age : null,
            'emp_lastname'          => $f20->people->lastname,
            'emp_middlename'        => $f20->people->middlename,
            'emp_name'              => $f20->people->name,
            'emp_gender'            => $f20->people->gender->name == 'FEMENINO' ? 'F' : 'M',
            'emp_discapabilities'   => $f20->people->sick ? strtoupper($f20->people->sick) : null,
            'emp_soons'             => $f20->people->hasSoon ?? null,
            'emp_phone'             => $f20->people->phone,
            'emp_email'             => $f20->people->email ? strtolower($f20->people->email) : '-',

            'supervisor'            => isset($f20->supervisor->supervisorUser->profile) ? strtoupper($f20->supervisor->supervisorUser->profile->name . ' ' . $f20->supervisor->supervisorUser->profile->lastname . ' ' . $f20->supervisor->supervisorUser->profile->middlename) : null,

            'city'                  => $f20->city->name ?? null,
            'province'              => $f20->province->name ?? null,
            'district'              => $f20->district->name ?? null,
            'address'               => $f20->address ?? null,
            'ruc'                   => $f20->ruc ?? null,
            'nameMype'              => $f20->nameMype ?? null,

            'econimic_sector'       => $f20->economicsector->name ?? null,
            'activity_comercial'    => $f20->comercialactivity->name ?? null,
            'date_reception'        => $f20->dateReception ? \Carbon\Carbon::parse($f20->dateReception)->format('d/m/Y') : null,
            'date_tramite'          => $f20->dateTramite ? \Carbon\Carbon::parse($f20->dateTramite)->format('d/m/Y') : null,
            'name_mype'             => strtoupper($f20->nameMype),
            'type_regimen'          => $f20->regime->name,
            'bic'                   => $f20->isbic,
            'num_solicitud'         => $f20->numbernotary,
            'notaria'               => isset($f20->notary->name) ? strtoupper($f20->notary->name) : null,
            'type_aporte'           => optional($f20->typecapital)->name,
            'monto_capital'         => $f20->montocapital,
            'modality'              => $f20->modality->name ?? null,


            // ids
            'regime_id'             => $f20->regime->id ?? null,
            'econimic_sector_id'    => $f20->economicsector->id ?? null,
            'activity_comercial_id' => $f20->comercialactivity->id ?? null,
            'notary_id'             => $f20->notary->id ?? null,
            'typecapital_id'        => $f20->typecapital->id ?? null,
            'modality_id'           => $f20->modality->id ?? null,
            'city_id'               => $f20->city->id ?? null,
            'province_id'           => $f20->province->id ?? null,
            'district_id'           => $f20->district->id ?? null,
        ];
    }


    // HISTORIAL DE REGISTROS...
    public function getByPeopleIdRegisters($peopleId)
    {
        $advisories = Advisory::where('people_id', $peopleId)
            ->with('user.profile', 'component', 'theme', 'modality', 'city', 'province', 'district')
            ->get()
            ->map(function ($advisory) {
                return [
                    'id' => $advisory->id,
                    'createDate' => $advisory->created_at,
                    'updateDate' => $advisory->updated_at,
                    'asesor' => strtoupper($advisory->user->profile->name . ' ' . $advisory->user->profile->lastname . ' ' . $advisory->user->profile->middlename),
                    'component' => $advisory->component->name,
                    'theme' => $advisory->theme->name,
                    'modality' => $advisory->modality->name,
                    'city' => $advisory->city->name,
                    'province' => $advisory->province->name,
                    'district' => $advisory->district->name
                ];
            })->sortByDesc('created_at');


        $formalization10 = Formalization10::where('people_id', $peopleId)
            ->with('detailprocedure', 'modality', 'economicsector', 'comercialactivity', 'city', 'province', 'district', 'user.profile')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'createDate' => $item->created_at,
                    'updateDate' => $item->updated_at,
                    'detailprocedure' => $item->detailprocedure->name,
                    'modality' => $item->modality->name,
                    'economicsector' => $item->economicsector->name,
                    'comercialactivity' => $item->comercialactivity->name,
                    'city' => $item->city->name,
                    'ruc' => $item->ruc,
                    'province' => $item->province->name,
                    'district' => $item->district->name,
                    'asesor' => strtoupper($item->user->profile->name . ' ' . $item->user->profile->lastname . ' ' . $item->user->profile->middlename)
                ];
            })->sortByDesc('created_at');


        $formalization20 = Formalization20::where('people_id', $peopleId)
            ->with('economicsector', 'comercialactivity', 'regime', 'city', 'province', 'district', 'modality', 'notary', 'mype', 'user.profile')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'createDate' => $item->created_at,
                    'task' => $item->task,
                    // 'codesunarp' => $item->codesunarp ? $item->codesunarp : '-',
                    'numbernotary' => $item->numbernotary ? $item->numbernotary : '-',
                    'address' => $item->address ? $item->address : '-',
                    'economicsector' => $item->economicsector ? $item->economicsector->name : '-',
                    'comercialactivity' => $item->comercialactivity ? $item->comercialactivity->name : '-',
                    'regime' => $item->regime ? $item->regime->name : '-',
                    'city' => $item->city ? $item->city->name : '-',
                    'province' => $item->province ? $item->province->name : '-',
                    'district' => $item->district ? $item->district->name : '-',
                    'modality' => $item->modality ? $item->modality->name : '-',
                    'notary' => $item->notary ? $item->notary->name : '-',
                    'mypename' => $item->nameMype,
                    'ruc' => $item->ruc ? $item->ruc : 'EN TRÁMITE',
                    'asesor' => $item->user->profile->name . ' ' . $item->user->profile->lastname . ' ' . $item->user->profile->middlename
                ];
            })->sortByDesc('created_at');


        $data = [
            'advisories' => $advisories,
            'formalization10' => $formalization10,
            'formalization20' => $formalization20,
        ];


        return response()->json(['data' => $data, 'status' => 200]);
    }


    // las formalizaciones de ruc 10 con componente = formalizacion componente_tema = 18
    public function updateAdvisoryToFormalizations($type)
    {

        if ($type == 'ruc10') {
            Formalization10::chunk(50, function ($formalizations) {
                foreach ($formalizations as $formalization) {
                    try {

                        // Verificar si ya existe un registro con los mismos valores
                        $exists = Advisory::where('people_id', $formalization->people_id)
                            ->where('ruc', $formalization->ruc)
                            ->where('user_id', $formalization->user_id)
                            ->exists();

                        if (!$exists) {

                            Advisory::create([
                                'economicsector_id' => $formalization->economicsector_id,
                                'comercialactivity_id' => $formalization->comercialactivity_id,
                                'observations' => null,
                                'user_id' => $formalization->user_id,
                                'people_id' => $formalization->people_id,
                                'component_id' => 4,
                                'theme_id' => 18,
                                'modality_id' => $formalization->modality_id,
                                'city_id' => $formalization->city_id,
                                'province_id' => $formalization->province_id,
                                'district_id' => $formalization->district_id,
                                'ruc' => $formalization->ruc,
                                'dni' => $formalization->dni,
                                'cde_id' => $formalization->cde_id,
                            ]);
                        }
                    } catch (\Exception $e) {

                        return response()->json(['message' => 'Error',  'status' => 409, 'error' => $e]);
                    }
                }
            });

            return response()->json(['message' => 'Actualización de asesorias actualizado RUC 10',  'status' => 200]);
        }

        if ($type == 'ruc20') {
            Formalization20::chunk(50, function ($formalizations) {
                foreach ($formalizations as $formalization) {
                    try {

                        $exists = Advisory::where('people_id', $formalization->people_id)
                            ->where('economicsector_id', $formalization->economicsector_id)
                            ->where('comercialactivity_id', $formalization->comercialactivity_id)
                            ->exists();

                        if (!$exists) {

                            Advisory::create([
                                'economicsector_id' => $formalization->economicsector_id,
                                'comercialactivity_id' => $formalization->comercialactivity_id,
                                'observations' => null,
                                'user_id' => $formalization->user_id,
                                'people_id' => $formalization->people_id,
                                'component_id' => 4,
                                'theme_id' => 16,
                                'modality_id' => $formalization->modality_id,
                                'city_id' => $formalization->city_id,
                                'province_id' => $formalization->province_id,
                                'district_id' => $formalization->district_id,
                                'ruc' => $formalization->ruc,
                                'dni' => $formalization->dni,
                                'cde_id' => $formalization->cde_id,
                            ]);
                        }
                    } catch (\Exception $e) {

                        return response()->json(['message' => 'Error',  'status' => 409, 'error' => $e]);
                    }
                }
            });
            return response()->json(['message' => 'Actualización de asesorias actualizado RUC 20',  'status' => 200]);
        }
    }














    // NUEVO DATATABLES

    public function indexDataTableAdvisories(Request $request)
    {
        $perPage = $request->input('length', 10);
        $page = ($request->input('start', 0) / $perPage) + 1;

        // Búsqueda global
        $search = $request->input('search.value');
        $query = Advisory::with([
            'economicsector',
            'comercialactivity',
            'user.profile',
            'people',
            'component',
            'theme',
            'modality',
            'city',
            'province',
            'district',
            'cde'
        ]);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('ruc', 'like', "%{$search}%")
                    ->orWhere('dni', 'like', "%{$search}%")

                    ->orWhereHas('economicsector', fn($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('comercialactivity', fn($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('user.profile', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('lastname', 'like', "%{$search}%")
                            ->orWhere('middlename', 'like', "%{$search}%");
                    })

                    // ->orWhereHas('people', fn($q) => $q->where('name', 'like', "%{$search}%") . orWhere('lastname', 'like', "%{$search}%"))
                    ->orWhereHas('component', fn($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('theme', fn($q) => $q->where('name', 'like', "%{$search}%"))

                    ->orWhereHas('city', fn($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('province', fn($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('district', fn($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('cde', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        // Ordenación
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDirection = $request->input('order.0.dir', 'asc');
        $columns = ["id", "ruc", "dni", "observations"];
        $orderColumn = $columns[$orderColumnIndex] ?? "id";
        $query->orderBy($orderColumn, $orderDirection);

        // Paginación
        if ($perPage == -1) {
            $perPage = $query->count();
        }
        $advisories = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => Advisory::count(),
            "recordsFiltered" => $advisories->total(),
            "data" => collect($advisories->items())->map(function ($advisory) {
                return [
                    "id" => $advisory->id,
                    'created_at' => Carbon::parse($advisory->created_at)->format('d/m/Y H:i A'),
                    'asesor' => $advisory->user->profile->name,





                    "ruc" => $advisory->ruc,
                    "dni" => $advisory->dni,
                    "observations" => $advisory->observations,
                    "modality_name" => optional($advisory->modality)->name, // Solo devuelve el nombre de la modalidad
                ];
            })
        ]);
    }
}
