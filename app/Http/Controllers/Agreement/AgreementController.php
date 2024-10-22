<?php

namespace App\Http\Controllers\Agreement;

use App\Exports\AgreementExport;
use App\Http\Controllers\Controller;
use App\Jobs\SendEndDateNotification;
use App\Models\Agreement;
use App\Models\Commitment;
use App\Models\AgreementActions;
use App\Models\AgreementCommitments;
use App\Models\AgreementFiles;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Jobs\SendEndDateNotificationUGSE;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;


class AgreementController extends Controller
{

    public function index(Request $request, $entity)
    {
        if (!in_array($entity, ['ugo', 'ugse'])) {
            return response()->json(['error' => 'Entidad no válida'], 400);
        }

        $search = $request->input('search');

        $query = Agreement::with([
            'estadoOperatividad',
            'estadoConvenio',
            'region',
            'provincia',
            'distrito',
            'acciones',
            'archivosConvenios',
        ])->search($search)
            ->where('entity', $entity)
            ->orderBy('created_at', 'desc');

        $data = $query->paginate(50);

        $data->getCollection()->transform(function ($item) {
            return [
                'id'                =>      $item->id,
                'city'              =>      $item->region->name,
                'city_id'           =>      $item->region->id,
                'province'          =>      $item->provincia->name,
                'province_id'       =>      $item->provincia->id,
                'district'          =>      $item->distrito->name,
                'district_id'       =>      $item->distrito->id,
                'entity'            =>      $item->alliedEntity,
                'startOperations'   =>      $item->homeOperations,
                'startDate'         =>      $item->startDate,
                'external'          =>      $item->external,
                'years'             =>      $item->years,
                'endDate'           =>      $item->endDate,
                'observations'      =>      $item->observations,
                'archivos'          =>      $item->archivosConvenios,
                'ruc'               =>      $item->ruc,
                'components'        =>      $item->components,
                'focal'             =>      $item->focal,
                'focalCargo'        =>      $item->focalCargo,
                'focalPhone'        =>      $item->focalPhone,
                'aliado'            =>      $item->aliado,
                'aliadoPhone'       =>      $item->aliadoPhone
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function allActionsById($id)
    {
        $data = AgreementActions::where('agreements_id', $id)->get()->makeHidden(['created_at', 'updated_at', 'deleted_at']); //🚩

        return response()->json(['data' => $data, 'status' => 200]);
    }

    public function updateActionById(Request $request, $id)
    {
        $request->validate([
            'description' => 'required|string|max:250',
        ]);
        $action = AgreementActions::findOrFail($id);
        $action->description = $request->input('description');
        $action->save();

        return response()->json(['message' => 'Actualizado correctamente', 'status' => 200]);
    }


    // **************************************************************** convenios UGO

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'city_id'           =>      'required|exists:cities,id',
                'province_id'       =>      'required|exists:provinces,id',
                'district_id'       =>      'required|exists:districts,id',
                'alliedEntity'      =>      'required|string|max:100',
                'homeOperations'    =>      'nullable|string|max:100',
                'startDate'         =>      'nullable|date',
                'years'             =>      'required',
                'endDate'           =>      'nullable|date',
                'external'          =>      'nullable',
                'observations'      =>      'nullable|string',
                'entity'            =>      'required',
                'created_id'        =>      'required|exists:users,id'
            ]);

            $convenio = Agreement::create($validatedData);

            if (! is_null($convenio->endDate)) {
                $endDate = Carbon::parse($convenio->endDate);
                SendEndDateNotification::dispatch($convenio)->delay($endDate->subDays(60));
                SendEndDateNotification::dispatch($convenio)->delay($endDate->subDays(30));

                // $testDelay = 10;
                // SendEndDateNotification::dispatch($convenio)->delay(now()->addSeconds($testDelay));
            }

            return response()->json(['message' => 'Convenio creado con éxito', 'status' => 200]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error:' => $e, 'status' => 500]);
        } catch (QueryException $e) {
            return response()->json(['message' => 'Existe un error', 'error' => $e], 400);
        }
    }



    // **************************************************************** convenios UGSE

    public function storeUgse(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'city_id'           =>      'required|exists:cities,id',
                'province_id'       =>      'required|exists:provinces,id',
                'district_id'       =>      'required|exists:districts,id',
                'alliedEntity'      =>      'required|string|max:100',
                'ruc'               =>      'nullable|max:11',
                'components'        =>      'nullable|max:18',
                'startDate'         =>      'nullable|date',
                'years'             =>      'required',
                'endDate'           =>      'nullable|date',
                'aliado'            =>      'nullable',
                'aliadoPhone'       =>      'nullable',
                'focal'             =>      'nullable',
                'focalCargo'        =>      'nullable',
                'focalPhone'        =>      'nullable',
                'renovation'        =>      'nullable',
                'observations'      =>      'nullable|string',
                'entity'            =>      'required',
                'created_id'        =>      'required|exists:users,id'
            ]);

            $convenio = Agreement::create($validatedData);

            if (!is_null($convenio->endDate)) {
                // $endDate = Carbon::parse($convenio->endDate);
                // SendEndDateNotificationUGSE::dispatch($convenio)->delay($endDate->subDays(60));
                // SendEndDateNotificationUGSE::dispatch($convenio)->delay($endDate->subDays(30));
                // $testDelay = 10;
                // SendEndDateNotificationUGSE::dispatch($convenio)->delay(now()->addSeconds($testDelay));
            }

            return response()->json(['message' => 'Convenio creado con éxito', 'status' => 200]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error:' => $e, 'status' => 500]);
        } catch (QueryException $e) {
            return response()->json(['message' => 'Existe un error', 'error' => $e], 400);
        }
    }

    public function storeAction(Request $request)
    {
        try {
            $request->validate([
                'description' => 'required|string|max:250',
                'agreements_id' => 'required|exists:agreements,id',
            ]);

            AgreementActions::create($request->all());

            return response()->json(['message' => 'Acción asignada al convenio correctamente.', 'status' => 200]);
        } catch (QueryException $e) {
            return response()->json(['message' => 'El usuario se registró pero la relación ha fallado', 'error' => $e], 400);
        }
    }

    public function deleteAgreement($id)
    {
        $user_role = getUserRole();
        $role_array = $user_role['role_id'];
        $user_id = $user_role['user_id'];

        if (in_array(5, $role_array) || in_array(7, $role_array) || in_array(8, $role_array)) {
            $agreement = Agreement::findOrFail($id);
            $agreement->delete();
            return response()->json(['message' => 'Eliminado del registro', 'status' => 200]);
        }
    }

    public function deleteActionById($id)
    {
        $action = AgreementActions::findOrFail($id);
        $action->delete();
        return response()->json(['message' => 'Eliminado del registro', 'status' => 200]);
    }

    public function upFileAgreement(Request $request)                                                                               // SUBIR ARCHVIVOS
    {
        $request->validate([
            'file' => 'required|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:20480', // Máximo 20MB
            'agreements_id' => 'required|integer|exists:agreements,id',
        ]);

        $file = $request->file('file');
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('filesagreements', $filename, 'public');

        $filesAgreement = new AgreementFiles();
        $filesAgreement->name = $file->getClientOriginalName();
        $filesAgreement->path = $path;
        $filesAgreement->agreements_id = $request->input('agreements_id');
        $filesAgreement->save();

        return response()->json(['message' => 'El archivo se ha subido', 'status' => 200]);
    }

    public function listAllFilesById($agreements_id)
    {
        $files = AgreementFiles::where('agreements_id', $agreements_id)->get();

        return response()->json(['data' => $files, 'status' => 200]);
    }

    public function download($id)
    {
        $fileAgreement = AgreementFiles::findOrFail($id);

        $filePath = 'public/' . $fileAgreement->path;

        if (! Storage::exists($filePath)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return Storage::download($filePath);
    }

    public function deleteFileById($id)
    {
        $user_role = getUserRole();
        $role_array = $user_role['role_id'];
        $user_id = $user_role['user_id'];

        if (in_array(5, $role_array) || in_array(7, $role_array)  || in_array(8, $role_array)) {
            $action = AgreementFiles::findOrFail($id);
            $action->delete();

            return response()->json(['message' => 'Archivo eliminado exitosamente', 'status' => 200]);
        }

        // $files = AgreementFiles::where('id', $id)
        //         ->where('user_id', $user_id)
        //         ->first();

        // if ($files) {
        //     $files->delete();
        //     return response()->json(['message' => 'Commitment deleted successfully.', 'status' => 200]);
        // } else {
        //     return response()->json(['error' => 'You do not have permission to delete this commitment or commitment not found.'], 403);
        // }

    }

    public function updateValuesAgreement(Request $request, $id)
    {
        $agreement = Agreement::findOrFail($id);

        $agreement->update($request->all());

        return response()->json(['message' => 'Datos actualizados', 'status' => 200]);
    }

    // DESCARGAR
    public function exportAgreement()
    {
        $query = Agreement::with([
            'estadoOperatividad',
            'estadoConvenio',
            'region',
            'provincia',
            'distrito',
            'acciones',
            'archivosConvenios',
        ]);

        $query->latest();
        $data = $query->get();

        $result = $data->map(function ($item, $index) {
            return [
                'index' => $index + 1,
                'city' => $item->region->name,
                'province' => $item->provincia->name,
                'district' => $item->distrito->name,
                'cdeAgente' => $item->external == 1 ? 'Externo' : 'PNTE',
                'entity' => $item->alliedEntity,
                'startOperations' => Carbon::parse($item->homeOperations)->format('d-m-Y'),
                'startDate' => Carbon::parse($item->startDate)->format('d-m-Y'),
                'years' => $item->years,
                'endDate' => Carbon::parse($item->endDate)->format('d-m-Y'),
                'status' => ' ',
                'observations' => str_replace("\n", ' ', $item->observations),
            ];
        });

        // return $result;

        return Excel::download(new AgreementExport($result), 'agreements.xlsx');
    }



    // COMPROMISOS

    public function createCompromission(Request $request)
    {
        $user_role = getUserRole();

        $user_role['user_id'];                                      // user_id

        $request->validate([
            'accion' => 'required|string',
            'date' => 'nullable|date',
            'modality' => 'nullable|string|max:1',
            'address' => 'nullable|string|max:100',
            'participants' => 'nullable|integer',
            'details' => 'nullable|string',
            'agreement_id' => 'required|exists:agreements,id',
            'file1' => 'nullable|file',
            'file2' => 'nullable|file',
            'file3' => 'nullable|file',
        ]);

        // Arreglo para almacenar las rutas de los archivos guardados
        $filePaths = [];
        $fileNames = [];

        // Guardar archivos
        foreach (['file1', 'file2', 'file3'] as $fileKey) {
            if ($request->hasFile($fileKey)) {
                $file = $request->file($fileKey);
                // Almacenar el archivo en el directorio 'public/compromisos'
                try {
                    $filePath = $file->store('compromisos', 'public');
                    $filePaths[$fileKey . '_path'] = $filePath;
                    $fileNames[$fileKey . '_name'] = $file->getClientOriginalName();
                } catch (\Exception $e) {
                    return response()->json(['message' => "Error al guardar el archivo: {$fileKey}. Error: {$e->getMessage()}", 'status' => 500]);
                }
            }
        }

        try {
            AgreementCommitments::create([
                'accion' => $request->input('accion'),
                'date' => $request->input('date'),
                'modality' => $request->input('modality'),
                'address' => $request->input('address'),
                'participants' => $request->input('participants'),
                'file1_path' => $filePaths['file1_path'] ?? null,
                'file1_name' => $fileNames['file1_name'] ?? null,
                'file2_path' => $filePaths['file2_path'] ?? null,
                'file2_name' => $fileNames['file2_name'] ?? null,
                'file3_path' => $filePaths['file3_path'] ?? null,
                'file3_name' => $fileNames['file3_name'] ?? null,
                'details' => $request->input('details'),
                'agreement_id' => $request->input('agreement_id'),
                'user_id' => $user_role['user_id'],
                'commitment_id' => $request->input('commitment_id'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al guardar los datos en la base de datos: ' . $e->getMessage(), 'status' => 500]);
        }

        return response()->json(['message' => 'Se registro', 'status' => 200]);
    }

    // ver lista de acciones x compromiso
    public function listCompromission($id)
    {
        $data = AgreementCommitments::where('commitment_id', $id)
            ->with(['profile:id,user_id,name,lastname,middlename'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $data, 'status' => 200]);
    }

    public function downloadCompromission($path)
    {
        $ruta = storage_path("app/public/{$path}");

        if (file_exists($ruta)) {
            return response()->download($ruta);
        } else {
            return response()->json(['message' => 'Archivo no encontrado', 'status' => 404], 404);
        }
    }


    // RESUMEN GENERAL DE CADA CONVENIO DE UGSE

    // public function resumenGeneral($id)
    // {
    //     $evento = Agreement::with([
    //         'profile:id,user_id,name,lastname,middlename',
    //         'region',
    //         'provincia',
    //         'distrito',
    //         'archivosConvenios',
    //         'filesAgreements',
    //         'compromisos.profile:id,user_id,name,lastname,middlename',
    //         'compromisos.acciones',
    //         'compromisos.acciones.profile:id,user_id,name,lastname,middlename'

    //     ])->findOrFail($id);

    //     return response()->json($evento);
    // }

    public function resumenGeneral($id)
    {
        $start = request()->query('start');
        $end = request()->query('end');

        $startDate = $start ?  $start : null;
        $endDate = $end ?  $end : null;

        $evento = Agreement::with([
            'profile:id,user_id,name,lastname,middlename',
            'region',
            'provincia',
            'distrito',
            'archivosConvenios',
            'filesAgreements',
            'compromisos.profile:id,user_id,name,lastname,middlename',
            'compromisos.acciones' => function ($query) use ($startDate, $endDate) {
                if ($startDate && $endDate) {
                    $query->whereBetween('date', [$startDate, $endDate]);
                }
            },
            'compromisos.acciones.profile:id,user_id,name,lastname,middlename'
        ])->findOrFail($id);

        return response()->json($evento);
    }

    public function deleteCommitment($id)
    {
        $user_role = getUserRole();
        $role_array = $user_role['role_id'];
        $user_id = $user_role['user_id'];

        if (in_array(5, $role_array) || in_array(8, $role_array)) {

            $commitment = AgreementCommitments::find($id);

            if ($commitment) {
                $commitment->delete();
                return response()->json(['message' => 'Commitment deleted successfully.', 'status' => 200]);
            } else {
                return response()->json(['error' => 'Commitment not found.'], 404);
            }
        }

        $commitment = AgreementCommitments::where('id', $id)
                ->where('user_id', $user_id)
                ->first();

        if ($commitment) {
            $commitment->delete();
            return response()->json(['message' => 'Commitment deleted successfully.', 'status' => 200]);
        } else {
            return response()->json(['error' => 'You do not have permission to delete this commitment or commitment not found.'], 403);
        }
    }


    // CONVENIOS HANNA

    public function createConvenioMetas(Request $request)
    {
        $user_role = getUserRole();
        $user_id = $user_role['user_id'];

        $data = $request->all();
        $data['user_id'] = $user_id;

        Commitment::create($data);

        return response()->json(['message' => 'Compromiso registrado', 'status' => 200]);
    }

    public function allCommitments($id)
    {
        $commitments = Commitment::with([
            'profile:id,user_id,name,lastname,middlename',
            'commitments'
        ])
        ->where('agreement_id', $id)
        ->orderBy('created_at', 'desc') // Ordenar por los más recientes
        ->get();

        return response()->json(['data' => $commitments, 'status' => 200]);
    }

    public function updateCommitment(Request $request, $id)
    {
        $commitment = Commitment::findOrFail($id);

        $commitment->update($request->all());

        return response()->json(['message' => 'Se actualizaron los datos', 'status' => 200]);
    }


    // CHART
    public function chatAgreement($name)
    {
        $today = Carbon::now();

        $agreements = Agreement::select('id', 'alliedEntity', 'endDate')
            ->where('entity', $name)
            ->get();

        $labels = [];
        $totalData = [];
        $pointBackgroundColor = [];
        $totalAgreements = 0;
        $countUnderThreeMonths = 0;
        $countUnderOneMonth = 0;

        foreach ($agreements as $agreement) {
            $endDate = Carbon::parse($agreement->endDate);
            $daysLeft = $today->diffInDays($endDate, false);

            $monthsLeft = $today->diffInMonths($endDate);
            $totalAgreements++;  // Contador de acuerdos totales

            if ($monthsLeft > 3) {
                $color = '#1ed900'; // Verde
            } elseif ($monthsLeft == 2) {
                $color = '#ffc107'; // Amarillo
                $countUnderThreeMonths++;  // Contador para acuerdos con menos de 3 meses
            } elseif ($monthsLeft <= 1) {
                $color = 'red';  // Rojo
                $countUnderOneMonth++;  // Contador para acuerdos con menos de 1 mes
            }

            $labels[] = $agreement->alliedEntity;  // Nombre de la entidad aliada
            $totalData[] = $daysLeft;              // Número de días hasta la fecha de endDate
            $pointBackgroundColor[] = $color;      // Color basado en los meses restantes
        }

        return response()->json([
            'labels' => $labels,
            'totalData' => $totalData,
            'pointBackgroundColor' => $pointBackgroundColor,
            'total' => $totalAgreements,  // Total de acuerdos
            'menores_a_3_meses' => $countUnderThreeMonths,  // Acuerdos con menos de 3 meses
            'menores_a_1_mes' => $countUnderOneMonth,  // Acuerdos con menos de 1 mes
        ]);
    }



}
