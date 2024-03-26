<?php

namespace App\Http\Controllers;

use App\Exports\AsesoriasExport;
use App\Exports\FormalizationRUC10Export;
use App\Exports\FormalizationRUC20Export;
use Illuminate\Http\Request;
use App\Models\People;
use App\Models\Post_Person;
use App\Models\Notary;
use App\Models\Company;
use App\Models\Gpscde;
use App\Models\FormFormalization;
use App\Models\Formalization20;
use App\Models\Formalization10;
use App\Models\ComercialActivity;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

use App\Jobs\AcceptInvitationFormalizationJob;
use App\Models\Advisery;
use App\Models\Componenttheme;
use App\Models\Departament;
use App\Models\District;
use App\Models\FormalizationDigital;
use App\Models\Province;
use Illuminate\Pagination\LengthAwarePaginator;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Calculation\Category;

class FormalizationController extends Controller
{
    
    public function myFormalizationsRuc20($dni)
    {
        $data = Formalization20::where('dni', $dni)->whereNotIn('step', [3])->get();

        return response()->json(['data' => $data]);
    }

    public function chooseFormalizationRuc20($id)
    {
        $data = Formalization20::where('id', $id)->first();
        return response()->json(['data' => $data]);
    }

    public function setPersonPost(Request $request)
    {
        $assign = Post_Person::where('dni_people', $request->number)
            ->where('id_post', $request->id_post)
            ->first();

        if (!$assign) {
            Post_Person::create([
                'dni_people' => $request->number,
                'id_post' => $request->id_post,
                'status' => 1
            ]);
        }

        return response()->json(['message' => 'success']);
    }


    public function formalizationRuc20(Request $request)
    {
        $user = People::where('number_document', $request->created_dni)
                ->where('id', $request->created_by)
                ->first();

        if($user) {
            $formalization = Formalization20::where('dni', $request->dni)
            ->where('code_sid_sunarp', $request->code_sid_sunarp)
            ->first();

            if($formalization) {
                $formalization->update($request->except('created_by'));
                $message = "Los datos se han actualizado";
            } else {
                Formalization20::create($request->all());
                $message = "Se ha registrado con éxito";
            }

            return response()->json(['message' => $message]);
        } else {
            return "jjjj";
        }
    }

    public function getAllSelectNotary()
    {
        $notaries = Notary::where('status', 1)->get();

        $data = $notaries->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id
            ];
        });
        return response()->json(['data' => $data]);
    }

    public function getAllSelectComercialActivities()
    {
        $categories = ComercialActivity::where('status', 1)->get();
        $data = $categories->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id
            ];
        });
        return response()->json(['data' => $data]);
    }

    public function createComercialActivities(Request $request)
    {
        ComercialActivity::create($request->all());
        return response()->json(['message' => 'Categoría creada']);
    }

    public function formalizationToCompany(Request $request) 
    {
        $company = Company::where('ruc', $request->ruc)->first();

        if(!$company) {

            Company::create($request->all());
            return response()->json(['message' => 'Registro exitoso']);

        } else {
            return response()->json(['message' => 'Este RUC ya se encuentra registrado']);
        }
    }

    public function formalizationRuc10(Request $request)
    {
        $user = People::where('number_document', $request->created_dni)
                ->where('id', $request->created_by)
                ->first();

        if ($user) {
             Formalization10::create($request->all());
            return response()->json(['message' =>'Se ha completado con éxito el registro', 'status' => 200]);
        } else {
            return response()->json(['message' =>'Esta cuenta no puede hacer esto', 'status' => 400]);
        } 
    }

    // ---------------------------------------------------------------- -----ASESORIAS
    public function allThemesComponents()
    {
        $categories = Componenttheme::where('status', 1)->get();
        $data = $categories->map(function ($item) {
            return [
                'label' => strtoupper($item->name),
                'value' => $item->id
            ];
        });
        return response()->json(['data' => $data]);
    }

    public function createNewConsulting(Request $request)
    {
        $user = People::where('number_document', $request->created_dni)
        ->where('id', $request->created_by)
        ->first();
        

        if ($user) {
            Advisery::create($request->all());
            return response()->json(['message' =>'Asesoría registrada con éxito', 'status' => 200]);
        } else {
            return response()->json(['message' =>'Esta cuenta no puede hacer esto', 'status' => 400]);
        } 
    }

    // ---------------------------------------------------------------- -----HISTORIAL
    public function formalizationHistorial($dni,$id)
    {
        Carbon::setLocale('es');

        $formalization20 = Formalization20::with('acreated', 'aupdated', 'categories')
            ->where('dni', $dni)
            ->where('id_person', $id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        $formalization10 = Formalization10::with('acreated', 'categories')
            ->where('id_person', $id)
            ->get();
        
        $adviseries = Advisery::with('acreated', 'theme')
            ->where('id_person', $id)
            ->get();

        
        $ruc20 = $formalization20->map(function ($item) {
            $asesor_create = $item->acreated ? $item->acreated->last_name . ' ' . $item->acreated->middle_name . ' ' . $item->acreated->name : '-';
            $asesor_update = $item->aupdated ? $item->aupdated->last_name . ' ' . $item->aupdated->middle_name . ' ' . $item->aupdated->name : '-';
            $category_name = $item->categories ? $item->categories->name : 'N/A';

            return [
                'date_start' => ucfirst(Carbon::parse($item->created_at)->isoFormat('dd DD/MM/YYYY [-] HH:mm')),
                'date_last' => ucfirst(Carbon::parse($item->updated_at)->isoFormat('dd DD/MM/YYYY [-] HH:mm')),
                'step' => $item->step,
                'sector' => $item->economy_sector,
                'category' => $category_name,
                'codigosunarp' => $item->code_sid_sunarp,
                'asesor_create' => $asesor_create,
                'asesor_update' => $asesor_update  
            ];
        });

        $ruc10 = $formalization10->map(function ($item) {
            return [
                'date_start' => ucfirst(Carbon::parse($item->created_at)->isoFormat('dd DD/MM/YYYY [-] HH:mm')),
                'detail_procedure' => $item->detail_procedure,
                'modality' => $item->modality,
                'economy_sector' => $item->economy_sector,
                'category' => $item->categories->name,
                'asesor_create' => $item->acreated->last_name . ' ' . $item->acreated->middle_name . ', ' . $item->acreated->name
            ];
        });

        $adv = $adviseries->map(function ($item) {
            return [
                'date_start' => ucfirst(Carbon::parse($item->created_at)->isoFormat('dd DD/MM/YYYY [-] HH:mm')),
                'component' => $item->component,
                'tema_compoment' => $item->theme->name,
                'modality' => $item->modality,
                'description' => $item->description,
                'asesor_create' => $item->acreated->last_name . ' ' . $item->acreated->middle_name . ', ' . $item->acreated->name
            ];
        });

        return response()->json(['ruc20' => $ruc20, 'ruc10' => $ruc10, 'adv' => $adv, 'status' => 200]);

    }

    public function allAsesorias(Request $request)
    {
        Carbon::setLocale('es');

        $query = Advisery::with('acreated', 'theme', 'departmentx', 'provincex', 'districtx', 'person', 'components', 'supervisorx', 'solicitante')
        ->orderBy('created_at', 'desc')
        ->where('status', 1);

        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $query->where(function($q) use ($searchTerm) {
                $q->whereHas('acreated', function($query) use ($searchTerm) {
                    $query->where('last_name', 'like', "%$searchTerm%")
                          ->orWhere('middle_name', 'like', "%$searchTerm%")
                          ->orWhere('name', 'like', "%$searchTerm%");
                })
                ->orWhereHas('solicitante', function($query) use ($searchTerm) {
                    $query->where('name', 'like', "%$searchTerm%")
                          ->orWhere('last_name', 'like', "%$searchTerm%")
                          ->orWhere('middle_name', 'like', "%$searchTerm%")
                          ->orWhere('email', 'like', "%$searchTerm%")
                          ->orWhere('phone', 'like', "%$searchTerm%");
                });
            });
        }

        $data = $query->paginate(20)
        ->through(function($item) {
            
            $registrador = $item->acreated;
            
            $supervisador = $item->supervisorx ? People::where('id', $item->supervisorx->id_supervisor)->first() : null;

            
            $departamento = null;
            $provincia = null;
            $distrito = null;

            if ($registrador) { $departamento = Departament::where('idDepartamento', $registrador->department)->first(); }
       
            if ($registrador) { $provincia = Province::where('idProvincia', $registrador->province)->first(); }

            if ($registrador) { $distrito = District::where('idDistrito', $registrador->district)->first(); }

            return [
                'reg_nombres' => $registrador ? $registrador->last_name . ' ' . $registrador->middle_name . ', ' . $registrador->name : '',
                'reg_departamento' =>  $departamento ? $departamento->descripcion : null,
                'reg_pais' => 'Perú',
                'reg_provincia' =>  $provincia ? $provincia->descripcion : null,
                'reg_distrito' =>  $distrito ? $distrito->descripcion : null,
                
                'reg_tipodoc' => $registrador ? $registrador->document_type : null, 
                'reg_numdoc' => $registrador ? $registrador->number_document : null, 
                'reg_fecnac' => $registrador ? $registrador->birthdate : null, 

                'sol_apellidos' => $item->person ? $item->person->last_name . ' ' . $item->person->middle_name : null,
                'sol_nombres' => $item->person ? $item->person->name : null,
                'sol_genero' => $item->person ? strtoupper($item->person->gender) : null,
                'sol_discapacidad' => $item->person && $item->person->lession == 1 ? 'SI' : 'NO',
                'sol_telefono' => $item->person ? $item->person->phone : null,
                'sol_correo' => $item->person ? $item->person->email : null,

                'ase_fecha' => Carbon::parse($item->created_at)->format('d/m/Y'),

                'misupervisor' => $supervisador ? $supervisador->last_name. ' '. $supervisador->middle_name. ' '. $supervisador->name : ' - ',

                'mype_region' => $item->departmentx->descripcion,
                'mype_provincia' => $item->provincex->descripcion,
                'mype_distrito' => $item->districtx->descripcion,

                'componente' => $item->components->name,
                'tema_componente' => $item->theme->name,
                'observacion' => $item ? $item->person->description : null,
                'modalidad' => $item->modality == 1 ? 'VIRTUAL' : 'PRESENCIAL'
            ];
        });

        return response()->json($data);
    }

    public function allFormalizations10(Request $request)
    {
        Carbon::setLocale('es');

        $query = Formalization10::with(
            'categories', 'acreated', 'supervisorx', 'departmentx', 'provincex', 'districtx', 'prodecuredetail', 'economicsectors', 'solicitante'
        )
        ->orderBy('created_at', 'desc')
        ->where('status', 1);


        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $query->where(function($q) use ($searchTerm) {
                $q->whereHas('acreated', function($query) use ($searchTerm) {
                    $query->where('last_name', 'like', "%$searchTerm%")
                          ->orWhere('middle_name', 'like', "%$searchTerm%")
                          ->orWhere('name', 'like', "%$searchTerm%");
                })
                ->orWhereHas('solicitante', function($query) use ($searchTerm) {
                    $query->where('name', 'like', "%$searchTerm%")
                          ->orWhere('last_name', 'like', "%$searchTerm%")
                          ->orWhere('middle_name', 'like', "%$searchTerm%")
                          ->orWhere('email', 'like', "%$searchTerm%")
                          ->orWhere('phone', 'like', "%$searchTerm%");
                })
                ->orWhereHas('categories', function($query) use ($searchTerm) {
                    $query->where('name', 'like', "%$searchTerm%");
                });
            });
        }

        $data = $query->paginate(20)
        ->through(function($item) {

            // $registrador = People::where('number_document', $item->acreated->document_number)->first();
            
            $registrador = $item->acreated;

            $solicitante = People::where('id', $item->id_person)->first();
            
            $supervisador = $item->supervisorx ? People::where('id', $item->supervisorx->id_supervisor)->first() : null;

            $departamento = null;
            $provincia = null;
            $distrito = null;

            if ($registrador) { $departamento = Departament::where('idDepartamento', $registrador->department)->first(); }
       
            if ($registrador) { $provincia = Province::where('idProvincia', $registrador->province)->first(); }

            if ($registrador) { $distrito = District::where('idDistrito', $registrador->district)->first(); }

            return [
                'ase_fecha' => Carbon::parse($item->created_at)->format('d/m/Y'),
                
                'reg_nombres' => $registrador ? $registrador->last_name . ' ' . $registrador->middle_name . ', ' . $registrador->name : '',
                'reg_departamento' =>  $departamento ? $departamento->descripcion : null,
                'reg_provincia' =>  $provincia ? $provincia->descripcion : null,
                'reg_distrito' =>  $distrito ? $distrito->descripcion : null,
                'reg_tipodoc' => $registrador ? $registrador->document_type : null, 
                'reg_numdoc' => $registrador ? $registrador->number_document : null, 
                'reg_fecnac' => $registrador ? $registrador->birthdate : null, 
                'reg_pais' => 'Perú',

                'sol_apellidos' => $solicitante->last_name . ' ' . $solicitante->middle_name,
                'sol_nombres' => $solicitante->name,
                'sol_genero' => strtoupper($solicitante->gender),
                'sol_discapacidad' => $solicitante && $solicitante->lession == 1 ? 'SI' : 'NO',
                'sol_telefono' => $solicitante ? $solicitante->phone : null,
                'sol_correo' => $solicitante ? $solicitante->email : null,

                'tipo_formalizacion' => 'PPNN (RUC 10)',
                'misupervisor' => $supervisador ? $supervisador->last_name . ' ' . $supervisador->middle_name . ' ' . $supervisador->name : null,

                'mype_region' => $item->departmentx->descripcion,
                'mype_provincia' => $item->provincex->descripcion,
                'mype_distrito' => $item->districtx->descripcion,

                'detalle_tramite' => $item->prodecuredetail->name,
                'sector_economico' => $item->economicsectors->name,
                'atividad_comercial' => $item->categories->name,
                'modalidad' => $item->modality == 1 ? 'VIRTUAL' : 'PRESENCIAL'
            ];
        });
    
        return response()->json($data);
    }


    public function allFormalizations20(Request $request)
    {
        Carbon::setLocale('es');

        $query = Formalization20::with(
            'categories', 'acreated', 'supervisorx', 'departmentx', 'provincex', 'districtx', 'prodecuredetail', 'economicsectors', 'notary', 'solicitante'
        )
        ->orderBy('created_at', 'desc')
        ->where('status', 1);

        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $query->where(function($q) use ($searchTerm) {
                $q->where('code_sid_sunarp', 'like', "%$searchTerm%")
                  ->orWhereHas('acreated', function($query) use ($searchTerm) {
                      $query->where('last_name', 'like', "%$searchTerm%")
                            ->orWhere('middle_name', 'like', "%$searchTerm%")
                            ->orWhere('name', 'like', "%$searchTerm%");
                    })
                    ->orWhereHas('solicitante', function($query) use ($searchTerm) {
                        $query->where('name', 'like', "%$searchTerm%")
                            ->orWhere('last_name', 'like', "%$searchTerm%")
                            ->orWhere('middle_name', 'like', "%$searchTerm%")
                            ->orWhere('email', 'like', "%$searchTerm%")
                            ->orWhere('phone', 'like', "%$searchTerm%");
                    })
                    ->orWhereHas('categories', function($query) use ($searchTerm) {
                      $query->where('name', 'like', "%$searchTerm%");
                    });
                  
            });
        }


        $data = $query->paginate(20)
        ->through(function($item) {

            $registrador = $item->acreated;
            $solicitante = People::where('id', $item->id_person)->first();
            $supervisador = $item->supervisorx ? People::where('id', $item->supervisorx->id_supervisor)->first() : null;

            $departamento = null;
            $provincia = null;
            $distrito = null;

            if ($registrador) { $departamento = Departament::where('idDepartamento', $registrador->department)->first(); }
       
            if ($registrador) { $provincia = Province::where('idProvincia', $registrador->province)->first(); }

            if ($registrador) { $distrito = District::where('idDistrito', $registrador->district)->first(); }

            return [
                'ase_fecha' => Carbon::parse($item->created_at)->format('d/m/Y'),
                'codigo_sid_sunarp' => $item->code_sid_sunarp,

                'reg_nombres' => $registrador ? $registrador->last_name . ' ' . $registrador->middle_name . ', ' . $registrador->name : '',
                'reg_departamento' =>  $departamento ? $departamento->descripcion : null,
                'reg_provincia' =>  $provincia ? $provincia->descripcion : null,
                'reg_distrito' =>  $distrito ? $distrito->descripcion : null,
                'reg_tipodoc' => $registrador ? $registrador->document_type : null, 
                'reg_numdoc' => $registrador ? $registrador->number_document : null, 
                'reg_fecnac' => $registrador ? $registrador->birthdate : null, 
                'reg_pais' => 'Perú',

                'sol_apellidos' => $solicitante->last_name . ' ' . $solicitante->middle_name,
                'sol_nombres' => $solicitante->name,
                'sol_genero' => strtoupper($solicitante->gender),
                'sol_discapacidad' => $solicitante && $solicitante->lession == 1 ? 'SI' : 'NO',
                'sol_telefono' => $solicitante ? $solicitante->phone : null,
                'sol_correo' => $solicitante ? $solicitante->email : null,

                'misupervisor' => $supervisador ? $supervisador->last_name . ' ' . $supervisador->middle_name . ' ' . $supervisador->name : null,
                
                'tipo_formalizacion' => 'PPJJ (RUC 20)',
                'sector_economico' => $item->economicsectors->name,
                'atividad_comercial' => $item->categories->name,
                'mype_region' => $item->departmentx->descripcion,
                'mype_provincia' => $item->provincex->descripcion,
                'mype_distrito' => $item->districtx->descripcion,
                'mype_direccion' => $item->address,
                'mype_nombre' => $item->social_reason,
                'tipo_regimen' => strtoupper($item->type_regimen),
                'numero_envio_notaria' => $item->num_notary,
                'notaria' => $item->notary ? $item->notary->name : null,
                'modalidad' => $item->modality == 1 ? 'VIRTUAL' : 'PRESENCIAL',
                'ruc' => $item->ruc
            ];
        });
    
        return response()->json($data);
    }


    public function downloadAsesorias()
    {
        try {
            return Excel::download(new AsesoriasExport, 'asesorias.xlsx');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
        }
    }
    public function downloadFormalizationsRuc10()
    {
        try {
            return Excel::download(new FormalizationRUC10Export, 'formalizacionesRUC10.xlsx');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
        }
    }
    public function downloadFormalizationsRuc20()
    {
        try {
            return Excel::download(new FormalizationRUC20Export, 'formalizacionesRUC20.xlsx');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
        }
    }

    
    // ---------------------------------------------------------------- -----FORMALIZACION DESDE EL FORMULARIO
    public function formalizationPublicForm(Request $request)
    {
        $formalization = FormFormalization::where('dni', $request->dni)->first();
        if ($formalization) {
            $formalization->count += 1;
            $formalization->update($request->all());
        } else {
            FormFormalization::create($request->all());
        }

        return response()->json(['message' => 'Un asesor se comunicará contigo dentro de las 24 horas (No considerar sábados, domingos ni feriados), gracias', 'status' => '200']);
    }

    public function gpsCdes()
    {
        $data = Gpscde::select('lat_cde', 'log_cde', 'name_cde', 'id')->get()->map(function ($item) {
            return [
                'position' => [
                    'lat' => $item->lat_cde,
                    'lng' => $item->log_cde,
                ],
                'title' => $item->name_cde,
                'id' => $item->id
            ];
        });
    
        return response()->json(['data' => $data]);
    }

    public function formalizationSendEmail($dni)
    {
        $user = FormFormalization::where('dni', $dni)->first();

        if($user) {
            $email = $user->email;

            $formalizationform = [       
                'name_complete' => $user->name_lastname,
                'phone' => $user->phone,
                'id_gps_cdes' => $user->id_gps_cdes,
                'id' => $user->id
            ];

            AcceptInvitationFormalizationJob::dispatch($email, $formalizationform);
        }
    }

    // public function formalizationRecaptcha(Request $request)
    // {
    //     $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
    //         'secret' => '6LcQMYopAAAAAEHqCHDRyjOofIVdcSzxqlHM4mUS', 
    //         'response' => $request->input('recaptcha_token'),
    //         'remoteip' => $request->ip()
    //     ]);

    //     $responseData = $response->json();

    //     return $responseData;

    //     if ($responseData['success']) {
    //         return response()->json(['message' => 'Formulario enviado correctamente', 'status' => 200]);
    //     } else {
    //         return response()->json(['message' => 'Error al validar reCAPTCHA', 'status' => 400]);
    //     }
    // }

    public function formalizationDigitalCreate(Request $request)
    {
        $formalizationDigital = 
        FormalizationDigital::where('dni_person', $request->dni_person)->first();

        if ($formalizationDigital) {
            $formalizationDigital->update($request->all());
        } else {
            FormalizationDigital::create($request->all());
        }
        return response()->json(['message' => 'Registrado correctamente', 'status' => 200]);
    }

    public function formalizationDigitalList(Request $request)
    {
        $query = Post_Person::with(['people', 'digital'])
            ->where('status', 1)
            ->where('id_post', 5);

        if ($request->has('search')) {
            $searchField = $request->input('search');
            $query->whereHas('people', function ($query) use ($searchField) {
                $query->where('last_name', 'like', '%' . $searchField . '%')
                ->orWhere('middle_name', 'like', '%' . $searchField . '%')
                ->orWhere('name', 'like', '%' . $searchField . '%')
                ->orWhere('name', 'like', '%' . $searchField . '%')
                ->orWhere('phone', 'like', '%' . $searchField . '%')
                ->orWhere('number_document', 'like', '%' . $searchField . '%');
            });
        }

    
        if ($request->has('department')) {
            $searchField = $request->input('department');
            $query = FormalizationDigital::with(['people', 'digital'])->where('id_gps', 'like', '%' . $searchField . '%');
        }

    
        $data = $query->paginate(20)
            ->through(function ($item) {

                if ($item->digital->is_delete == 0) {
                    return [
                        'id_person' => $item->people->id,
                        'document_type' => $item->people->document_type,
                        'number_document' => $item->people->number_document,
                        'last_name' => ucfirst(strtolower($item->people->last_name)),
                        'middle_name' => ucfirst(strtolower($item->people->middle_name)),
                        'name' => ucwords(strtolower($item->people->name)),
                        'department' => $item->people->departament->descripcion,
                        'province' => Province::find($item->people->province)->descripcion,
                        'district' => District::find($item->people->district)->descripcion,
                        'phone' => $item->people->phone,
                        'email' => $item->people->email,
                        'id_gps' => $item->digital->id_gps,
                        'gps_name' => $item->digital->id_gps ? Gpscde::find($item->digital->id_gps)->name_cde : '-',
                        'status' => $item->digital->status == 0 ? false : true,
                        'booking' => $item->digital->booking,
                        'count' => $item->digital->count,
                        'created_by' => $item->people->created_by,
                        'update_by' => $item->people->update_by
                    ];
                } 
            });
        
            $data = collect($data)->filter(function ($item) {
                return $item !== null;
            });


        return response()->json($data);
    }

    public function showAllCdesFormalizations()
    {
        $result = FormalizationDigital::with('cdes')->get();
        $data = $result->map(function ($item) {
            if ($item->cdes) {
                return [
                    'value' => $item->cdes->id,
                    'label' => $item->cdes->name_cde
                ];
            }
            return null;
        })->filter()->unique('value')->values();

        return response()->json(['data' => $data]);
    }

    public function requestMyStatusFormalization($dni)
    {
        $query = FormalizationDigital::with('people')
        ->where('dni_person', $dni)
        ->first();

        if($query) {
            
            if($query->is_delete == 0) {
                $query->count++; 
                $query->save();
                $data = [
                    'name'=> $query->people->name . ' ' . $query->people->last_name,
                    'status' => $query->status,
                    'booking' => $query->booking
                ];
                return response()->json(['data' => $data, 'status' => 200]);
            } else {
                $query->is_delete = 0;
                $query->save();
            } 
        }
        return response()->json(['data' => null, 'status' => 400]);
    }

    public function updateStatusFormalization(Request $request, $dni)
    {
        $query = FormalizationDigital::where('dni_person', $dni)->first();

        if (!$query) {
            return response()->json(['message' => 'Formalization not found'], 404);
        }

        $request->status && $query->status = $request->input('status');
        $request->booking && $query->booking = $request->input('booking');
        $request->is_delete && $query->is_delete = $request->input('is_delete');
        $query->save();

        return response()->json(['message' => 'Registrado correctamente', 'status' => 200]);
    }
    
}

