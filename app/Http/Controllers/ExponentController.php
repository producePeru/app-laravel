<?php

namespace App\Http\Controllers;

use App\Models\Exponent;
use App\Http\Requests\StoreExponentRequest;
use App\Http\Requests\UpdateExponentRequest;
use App\Http\Resources\ExponentCollection;
use App\Http\Resources\ExponentResource;
use Illuminate\Http\Request;

class ExponentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $exponents = Exponent::paginate(20);
        return new ExponentCollection($exponents);
    }

    public function allExponents()
    {
       
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
    public function store(StoreExponentRequest $request)
    {
        try {
            $exponent = Exponent::create($request->all());
            return response()->json(['message' => 'Exponente creado correctamente'], 201);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error al crear el Exponnente. Por favor, inténtalo de nuevo.'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error desconocido al crear el exponente.', $e], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Exponent $exponent)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Exponent $exponent)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExponentRequest $request, Exponent $exponent)
    {
        $exponent->update($request->all());
        return response()->json(['message' => 'Exponente actualizado correctamente']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Exponent $exponent)
    {
        //
    }
}
