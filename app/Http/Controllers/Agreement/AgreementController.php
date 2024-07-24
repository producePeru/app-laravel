<?php

namespace App\Http\Controllers\Agreement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Agreement;
use App\Models\AgreementActions;
use App\Models\AgreementFiles;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;
use App\Jobs\SendEndDateNotification;
use App\Exports\AgreementExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class AgreementController extends Controller
{
    public function index(Request $request)
    {
        $order = $request->input('order', 'asc');
        $search = $request->input('search', '');
        $dateDiffOrder = $request->input('date_diff_order', 'desc');

        if (!in_array($order, ['asc', 'desc'])) {
            $order = 'asc';
        }
        if (!in_array($dateDiffOrder, ['asc', 'desc'])) {
            $dateDiffOrder = 'desc';
        }

        $query = Agreement::with(
            ['estadoOperatividad', 'estadoConvenio', 'region', 'provincia', 'distrito', 'acciones', 'archivosConvenios']
        )->join('cities', 'agreements.city_id', '=', 'cities.id')
        ->select('agreements.*', DB::raw('DATEDIFF(agreements.endDate, agreements.startDate) as date_diff'))
        ->orderBy('date_diff', $dateDiffOrder)
        ->orderBy('cities.name', $order);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('agreements.denomination', 'like', "%{$search}%")
                  ->orWhere('agreements.alliedEntity', 'like', "%{$search}%")
                  ->orWhere('agreements.address', 'like', "%{$search}%")
                  ->orWhere('agreements.reference', 'like', "%{$search}%")
                  ->orWhere('agreements.initials', 'like', "%{$search}%")
                  ->orWhere('cities.name', 'like', "%{$search}%");
            });
        }

        $data = $query->paginate(50);

        $data->getCollection()->transform(function ($item) {        // 🚩decode flat
            $item['initials'] = json_decode($item['initials']);
            return $item;
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

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'denomination' => 'required|string|max:100',
                'alliedEntity' => 'required|string|max:100',
                'homeOperations' => 'nullable|string|max:100',
                'address' => 'required|string|max:80',
                'reference' => 'required|string|max:100',
                'resolution' => 'required|string|max:150',
                'initials' => 'required|array',
                'startDate' => 'nullable|date',
                'endDate' => 'nullable|date',
                'city_id' => 'required|exists:cities,id',
                'province_id' => 'required|exists:provinces,id',
                'district_id' => 'required|exists:districts,id',
                'created_id' => 'required|exists:users,id'      // usuario_creador
                // 'operationalstatus_id' => 'required|exists:operationalstatus,id',
                // 'agreementstatus_id' => 'required|exists:agreementstatus,id',               // 🚩reference
            ]);

            $validatedData['initials'] = json_encode($validatedData['initials']);

            $convenio = Agreement::create($validatedData);

            if (!is_null($convenio->endDate)) {
                // $endDate = Carbon::parse($convenio->endDate);
                // SendEndDateNotification::dispatch($convenio)->delay($endDate->subDays(1));
                // SendEndDateNotification::dispatch($convenio)->delay($endDate->subDays(2));

                $testDelay = 10;
                SendEndDateNotification::dispatch($convenio)->delay(now()->addSeconds($testDelay));
            }

            return response()->json(['message' => 'Convenio creado con éxito', 'status' => 200]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error:' => $e,'status' => 500]);
        }
        catch (QueryException $e) {
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
        $agreement = Agreement::findOrFail($id);
        $agreement->delete();
        return response()->json(['message' => 'Acción eliminada exitosamente', 'status' => 200]);
    }

    public function deleteActionById($id)
    {
        $action = AgreementActions::findOrFail($id);
        $action->delete();
        return response()->json(['message' => 'Acción eliminada exitosamente', 'status' => 200]);
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

        return response()->json(['message' => 'El archivo se ha subido correctamente', 'status' => 200]);
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

        if (!Storage::exists($filePath)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return Storage::download($filePath);
    }

    public function deleteFileById($id)
    {
        $action = AgreementFiles::findOrFail($id);
        $action->delete();
        return response()->json(['message' => 'Archivo eliminado exitosamente', 'status' => 200]);
    }



    // DESCARGAR
    public function exportAgreement(Request $request)
    {
        return Excel::download(new AgreementExport, 'agreements.xlsx');
    }
}
