<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Workshop;
use App\Models\People;
use App\Models\Post;
use App\Models\Post_Person;
use App\Models\Company;
use App\Models\Mype;
use App\Models\CompanyPeople;
use App\Models\WorkshopDetails;
use App\Http\Requests\StoreInvitationRequest;
use App\Http\Requests\UpdateInvitationRequest;
use App\Jobs\AcceptInvitationWorkshopJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class InvitationController extends Controller
{
  
    public function createInvitation(Request $request, $workshopId)
    {

        $workshop = Workshop::find($workshopId);

        if (!$workshop) {
            return response()->json(['message' => 'Taller no encontrado'], 404);
        }

        if ($workshop->invitation_id !== null) {
            return response()->json(['message' => 'La invitación ya está asociado a este Taller'], 422);
        }

        $quillContent = $request->input('content');
        $idWorkshop = $request->input('workshop_id');

        try {
            // $invitation = Invitation::create($request->all());
            $invitation = Invitation::create(['content' => $quillContent, 'workshop_id' => $idWorkshop]);
            
            $workshop->update(['invitation_id' => $invitation->id]);
            
            return response()->json(['message' => 'Invitación creada correctamente'], 201);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error al crear la invitación. Por favor, inténtalo de nuevo.'. $e], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error desconocido al crear esta invitación.'. $e], 500);
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

    public function invitationContent($idInvitation)
    {
        $invitation = Invitation::find($idInvitation);

        $data = [
            'content' => $invitation->content
        ];

        try {
            return response()->json(['data' => $data], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Invitacion no encontrada'], 404);
        } 
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
            return response()->json(['error' => 'Error al actualizar esta invitación.', $e], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error desconocido al actualizar este test de entrada.', $e], 500);
        }
    }



    public function acceptedInvitation(Request $request)
    {
        //person

        $post = Post::find($request->post);

        if (!$post) {
            return response()->json(['message' => 'Este rol no existe'], 404);
        }

        $user = People::where('number_document', $request->number_document)->first();

        $person = [
            'document_type' => $request->document_type,
            'number_document' => $request->number_document,
            'last_name' => $request->last_name,
            'middle_name' => $request->middle_name,
            'name' => $request->name,
            'department' => $request->department,
            'province' => $request->province,
            'district' => $request->district,
            'phone' => $request->phone,
            'email' => $request->email,
            'created_by' => $request->created_by ? Crypt::decryptString($request->created_by) : null,
            'update_by' => $request->update_by ? Crypt::decryptString($request->update_by) : null,
            'post' => $request->post
        ];

       
        if ($user) {
            
            if($user['created_by'] || $user['update_by']) {
                $data = $request->except(['created_by', 'update_by']); 
            }

            $user->update($person);
            $mensaje = "Usuario actualizado exitosamente.";

        } else {

            People::create($person);
            $mensaje = "Usuario creado exitosamente.";

        }

        $assign = Post_Person::where('dni_people', $request->number_document)->where('id_post', $request->post)->first();

        if (!$assign) {
            Post_Person::create([
                'dni_people' => $request->number_document,
                'id_post' => $request->post,
                'status' => 1
            ]);
        } else {
            $assign->update(['status' => 1]);
        }



        //company

        $company = Company::where('ruc', $request->ruc)->first();

        $mype = [
            'ruc' => $request->ruc,
            'social_reason' => $request->social_reason,
            'category' => $request->category,
            'person_type' => $request->person_type,
            'created_by' => $request->created_by ? Crypt::decryptString($request->created_by) : null,
            'update_by' => $request->update_by ? Crypt::decryptString($request->update_by) : null
        ];

        if ($company) {

            $company->update($mype);

        } else {

            Company::create($mype);
        }

        // person_company

        $percomp = CompanyPeople::where('ruc', $request->ruc)
        ->where('number_document', $request->number_document)
        ->get();

        $personcompany = [
            'ruc' => $request->ruc,
            'number_document' => $request->number_document
        ];

        if ($percomp->isNotEmpty()) {
            $percomp->first()->update($personcompany);
        } else {

            CompanyPeople::create($personcompany);
        }


        // registrarme en el taller

        $workshopDetail = WorkshopDetails::where('workshop_id', $request->id_workshop)
        ->where('ruc_mype', $request->ruc)
        ->where('dni_mype', $request->number_document)
        ->first();

        $workDetail = [
            'ruc_mype' => $request->ruc,
            'dni_mype' => $request->number_document,
            'workshop_id' => $request->id_workshop
        ];

        if($workshopDetail) {
            $workshopDetail->update($workDetail);
        } else {
            WorkshopDetails::create($workDetail);
        }

        $email = $request->email;


        $workshop = Workshop::find($request->id_workshop);

        // detalles del taller
        $mype = [       
            'name_complete' => $request->name . ' ' . $request->last_name . ' ' . $request->middle_name,
            'title' => $workshop['title'],
            'workshop_date' => $workshop['workshop_date'],
            'link' => $workshop['link']
        ];
     
      
        AcceptInvitationWorkshopJob::dispatch($email, $mype);

        return $mype;

    }

}
