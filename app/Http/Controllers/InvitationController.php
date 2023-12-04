<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Workshop;
use App\Http\Requests\StoreInvitationRequest;
use App\Http\Requests\UpdateInvitationRequest;
use Illuminate\Http\Request;

class InvitationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function store(StoreInvitationRequest $request)
    {
    
    }

    public function createInvitation(Request $request, $workshopId)
    {
        $workshop = Workshop::find($workshopId);

        if (!$workshop) {
            return response()->json(['message' => 'Taller no encontrado'], 404);
        }

        if ($workshop->invitation_id !== null) {
            return response()->json(['message' => 'La invitación ya está asociado a este Taller'], 422);
        }

        try {
            $invitation = Invitation::create($request->all());
            $workshop->update(['invitation_id' => $invitation->id]);
            
            return response()->json(['message' => 'Invitación creada correctamente'], 201);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error al crear la invitación. Por favor, inténtalo de nuevo.'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error desconocido al crear esta invitación.', $e], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $invitation = Invitation::find($id);
        
        try {
            return response()->json(['data' => $invitation], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Invitacion no encontrada'], 404);
        } 
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Invitation $invitation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInvitationRequest $request, Invitation $invitation)
    {
        try {
            $invitation->update($request->all());
            return response()->json(['message' => 'La invitación se actualizó correctamente']);    
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error al actualizar esta invitación.'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error desconocido al actualizar este test de entrada.', $e], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invitation $invitation)
    {
        //
    }
}
