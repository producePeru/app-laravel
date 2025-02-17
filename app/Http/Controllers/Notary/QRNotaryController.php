<?php

namespace App\Http\Controllers\Notary;

use App\Http\Controllers\Controller;
use App\Models\QRNotaries;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class QRNotaryController extends Controller
{

    public function store(Request $request)
    {
        try {
            $data = $request->all();

            QRNotaries::create($data);

            return response()->json(['message' => 'Datos enviados', 'status' => 200]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error al procesar la solicitud',
                'error' => $th->getMessage(),
                'status' => 500
            ], 500);
        }
    }


    public function index(Request $request)
    {
        $search = $request->input('search');
        $year = $request->input('year');

        $query = QRNotaries::with([
            'typedocument',
            'economicsector'
        ])
            ->search($search)
            ->when($year, function ($q) use ($year) {
                $q->whereYear('created_at', $year);
            });

        $query->orderBy('created_at', 'desc');

        $data = $query->paginate(50);

        $data->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'typedocument_id' => optional($item->typedocument)->id,
                'typedocument_name' => optional($item->typedocument)->avr,
                'documentnumber' => $item->documentnumber,
                'nationality' => strtoupper($item->nationality),
                'name' => strtoupper($item->name),
                'lastname' => strtoupper($item->lastname),
                'middlename' => strtoupper($item->middlename),
                'phone' => $item->phone,
                'email' => $item->email,
                'economicsector_id' => optional($item->economicsector)->id,
                'economicsector_name' => optional($item->economicsector)->name,
                'motivo' => $item->motivo,
                'notary' => $item->notary,
                'califica' => $item->califica,
                'created_at' => Carbon::parse($item->created_at)->format('d/m/Y')
            ];
        });

        return response()->json(['data' => $data]);
    }
}
