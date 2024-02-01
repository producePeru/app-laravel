<?php

namespace App\Http\Controllers;

use App\Models\Workshop;
use App\Models\Testin;
use App\Models\Testout;
use App\Models\WorkshopDetails;
use App\Models\Invitation;
use App\Http\Requests\StoreWorkshopRequest;
use App\Http\Requests\UpdateWorkshopRequest;
use Illuminate\Http\Request;
use Carbon\Carbon;

class WorkshopController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $perPage = 20;

        $workshops = Workshop::with([
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

        // $now = Carbon::now()->timestamp;
        foreach ($workshops as $workshop) {
            // $workshopDate = Carbon::createFromFormat('d-m-Y H:i A', $workshop->workshop_date)->timestamp;
            // $workshop->status = ($now < $workshopDate) ? 'en curso' : 'finalizado';
            $workshop->registered_count = WorkshopDetails::where('workshop_id', $workshop->id)->count();
        }

        return response()->json(['workshop' => $workshops]);
    }

    /**
     * Show the form for creating a new resource.   21-12-2023 23:50 PM
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
        
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function getBySlug(Request $request, $slug)
    {
        $workshop = Workshop::where('slug', $slug)->with('exponent')->first();

        if (!$workshop) {
            return response()->json(['message' => 'Workshop not found'], 404);
        }

        $test_in = Testin::find($workshop->testin_id);
        $test_out = Testout::find($workshop->testout_id);

        $response = [
            'id' => $workshop->id,
            'id_in' => $workshop->testin_id,
            'id_out' => $workshop->testout_id,
            'id_invitation' => $workshop->invitation_id,
            'title' => $workshop->title,
            'workshop_date' => $workshop->workshop_date,
            'exponent_k' => $workshop->exponent->id,
            'exponent_name' => $workshop->exponent->first_name . ' ' . $workshop->exponent->last_name. ' ' . $workshop->exponent->middle_name,
            'test_in_date' => $test_in ? $test_in->date_end : null,
            'test_out_date' => $test_out ? $test_out->date_end : null,
            'link' => $workshop->link,
            'slug' => $workshop->slug,
            'type_intervention' => $workshop->type_intervention
        ];

        return response()->json(['workshop' => $response]);
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
    public function invitation($slug)
    {
        $result = Workshop::where('status', 1)
        ->where('slug', $slug)
        ->first();

        if(!$result) {
            return response()->json(['data' => []]);
        } else {
            
            $invitation = Invitation::where('workshop_id', $result->id)->first();

            if (!$invitation) {
                return response()->json(['data' => []]);
            }
            
            $data = [
                'id' => $result->id,
                'title' => $result->title,
                'slug' => $result->slug,
                'date' => $result->workshop_date,
                "workshop_id" => $invitation->workshop_id,
                "content" => $invitation->content
            ];

            return response()->json(['data' => $data]);
        }
    }
}
