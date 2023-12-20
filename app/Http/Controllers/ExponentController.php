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
        $enabledExponents = Exponent::where('enabled', 1)->get();

        $formattedExponents = $enabledExponents->map(function ($exponent) {
            $label = $exponent->first_name . ' ' . $exponent->last_name . ' ' . $exponent->middle_name;
            return [
                'label' => $label,
                'value' => $exponent->id
            ];
        });

        try {
            return response()->json(['data' => $formattedExponents], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error desconocido.'], 500);
        }
    }

    public function isEnabledDisabled($idExponent)
    {
        $exponent = Exponent::find($idExponent);
        
        if ($exponent) {
            $exponent->enabled = $exponent->enabled ? 0 : 1;
            $exponent->save();
            if($exponent->enabled == 1) {
                return response()->json(['message' => 'Exponente ha sido habilitado'], 201);
            } else {
                return response()->json(['message' => 'Exponente fue deshabilitado'], 201);
            }
        }
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
            return response()->json(['error' => 'Error desconocido al crear el exponente.'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($idExponent)
    {
        $exponent = Exponent::find($idExponent);

        try {
            return response()->json(['data' => $exponent], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Exponente no encontrado'], 404);
        }
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
