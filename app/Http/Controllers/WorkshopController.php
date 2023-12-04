<?php

namespace App\Http\Controllers;

use App\Models\Workshop;
use App\Http\Requests\StoreWorkshopRequest;
use App\Http\Requests\UpdateWorkshopRequest;

class WorkshopController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $perPage = 10;

        $workshop = Workshop::with([
            'exponent' => function ($query) { $query->select('id', 'first_name', 'last_name', 'middle_name'); },
            
        ])->select(
            'id', 
            'title', 
            'slug', 
            'exponent_id', 
            'workshop_date',
            'type_intervention',
            'testin_id', 
            'testout_id',
            'invitation_id',
            'status',
            'registered',
            'link',
            'rrss',
            'sms',
            'correo'
        )->orderBy('workshop_date', 'desc')->paginate($perPage);

        return response()->json(['workshop' => $workshop]);
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
    public function store(StoreWorkshopRequest $request)
    {
        try {
            $workshop = Workshop::create($request->all());
            return response()->json(['message' => 'Taller creado correctamente'], 201);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error al crear el taller. Por favor, inténtalo de nuevo.'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error desconocido al crear el taller', $e], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Workshop $workshop)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Workshop $workshop)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateWorkshopRequest $request, Workshop $workshop)
    {
        $workshop->update($request->all());
        return response()->json(['message' => 'Taller actualizado correctamente']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Workshop $workshop)
    {
        //
    }
}
