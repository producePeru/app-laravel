<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Controller;
use App\Models\Workshop;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class WorkshopController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // $workshops = Workshop::all();
        // return response()->json(['data' => $workshops, 'status' => 200]);
        $filters = [
            'asesor'    => $request->input('asesor'),
            'name'      => $request->input('name'),
            'dateStart' => $request->input('dateStart'),
            'dateEnd'   => $request->input('dateEnd'),
            'year'      => $request->input('year'),
        ];

        $paginate = $request->boolean('paginate', true);

        $userRole = getUserRole();

        $query = Workshop::query();

        $workshops = $query->paginate(150)->through(function ($item) {
            return $this->mapWorkshopItems($item);
        });

        return response()->json([
            'data'   => $workshops,
            'status' => 200
        ]);
    }

    private function mapWorkshopItems($ws)
    {
        return [
            'id'                    => $ws->id,
            'workshopName'          => $ws->workshopName ?? null,
            'date'                  => $ws->date ? \Carbon\Carbon::parse($ws->date)->format('d/m/Y') : null,
            'hour'                  => $ws->hour ?? null,
            'link'                  => $ws->link ?? null,
            'description'           => strip_tags($ws->description),
            'expositor'             => strtoupper($ws->expositor),
            'status_inv'            => $ws->status_inv,
            'status_te'             => $ws->status_te,
            'status_ts'             => $ws->status_ts
        ];
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'workshopName' => 'required|string|max:250',
                'date' => 'required|date',
                'hour' => 'required|string|max:20',
                'link' => 'required|string|max:255',
                'description' => 'nullable|string',
                'expositor' => 'nullable|string|max:100'
            ]);

            Workshop::create($validated);

            return response()->json(['message' => 'Taller registrado correctamente', 'status' => 200]);
        } catch (QueryException $e) {
            return response()->json(['message' => 'El usuario se registró pero la relación ha fallado', 'error' => $e], 400);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $workshop = Workshop::findOrFail($id);
        return response()->json($workshop);
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
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'workshopName' => 'required|string|max:250',
            'date' => 'required|date',
            'hour' => 'required|string|max:20',
            'link' => 'required|string|max:255',
            'description' => 'nullable|string',
            'expositor' => 'nullable|string|max:100',
        ]);

        $workshop = Workshop::findOrFail($id);
        $workshop->update($validated);
        return response()->json(['status' => 200, 'message' => 'Taller editado']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $workshop = Workshop::findOrFail($id);
        $workshop->delete();
        return response()->json(['message' => 'Taller eliminado correctamente']);
    }
}
