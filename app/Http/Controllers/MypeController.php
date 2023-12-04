<?php

namespace App\Http\Controllers;

use App\Models\Mype;
use Illuminate\Http\Request;
use App\Http\Requests\StoreMypeRequest;
use App\Http\Requests\UpdateMypeRequest;
use App\Http\Resources\MypeCollection;
use App\Http\Resources\MypeResource;
use App\Filters\MypeFilter;
use Illuminate\Database\QueryException;

class MypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $mype = Mype::paginate(50);
        return new MypeCollection($mype);
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
    public function store(StoreMypeRequest $request)
    {
        try {
            $mype = Mype::create($request->all());
            return response()->json(['message' => 'Mype creada correctamente'], 201);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error al crear la Mype. Por favor, inténtalo de nuevo.'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error desconocido al crear la Mype.'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Mype $mype)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Mype $mype)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMypeRequest $request, Mype $mype)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Mype $mype)
    {
        //
    }
}
