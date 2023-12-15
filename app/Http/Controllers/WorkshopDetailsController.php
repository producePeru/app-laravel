<?php

namespace App\Http\Controllers;

use App\Models\WorkshopDetails;
use App\Http\Requests\StoreWorkshopDetailsRequest;
use App\Http\Requests\UpdateWorkshopDetailsRequest;
use Illuminate\Http\Request;
use App\Models\Testin;
use App\Models\Testout;
use App\Models\Workshop;
use App\Models\Mype;
use Validator;
use Illuminate\Support\Facades\DB;
use App\Jobs\AcceptInvitationWorkshopJob;
use Carbon\Carbon;


class WorkshopDetailsController extends Controller
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
    public function store(StoreWorkshopDetailsRequest $request)
    {
        //
    }

    public function getWorkshopsGroupedByDate()
    {
        $workshops = Workshop::all();
        $groupedWorkshops = [];

        foreach ($workshops as $workshop) {
            $fechaCarbonString = $workshop->workshop_date;
            $fechaString = substr($fechaCarbonString, 0, 10);
            $fechaCarbon = Carbon::createFromFormat('d-m-Y', $fechaString);
            $workshopDate = $fechaCarbon->format('Y-m-d');
            
            if (!isset($groupedWorkshops[$workshopDate])) {
                $groupedWorkshops[$workshopDate] = [];
            }

            $statusType = '';

            switch ($workshop->status) {
                case 0:
                    $statusType = 'error';
                    break;
                case 1:
                    $statusType = 'success';
                    break;
                case 2:
                    $statusType = 'warning';
                    break;
                default:
                    $statusType = 'unknown';
                    break;
            }

            $groupedWorkshops[$workshopDate][] = ['content' => $workshop->title, 'type' => $statusType, 'slug' => $workshop->slug];
        }

        return response()->json($groupedWorkshops);
    }

    public function workshopDetails(Request $request, $id)
    {   
        $validator = Validator::make(['id' => $id], [
            'id' => 'required|integer',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $workshopDetails = WorkshopDetails::where('workshop_id', $id)
        ->orderBy('created_at', 'desc') 
        ->paginate($request->input('per_page', 10)); 

        return response()->json($workshopDetails);
    }

    public function averageWorkshopDetails(Request $request)
    {
        $workshopDetails = WorkshopDetails::all();
        foreach ($workshopDetails as $workshopDetail) {
            $workshopDetail->average_final = ($workshopDetail->te_note + $workshopDetail->ts_note) / 2;
            $workshopDetail->average_satisfaction = (($workshopDetail->c1 * 20)+($workshopDetail->c2 * 20)+($workshopDetail->c3 * 20)) / 3;
            
            $workshopDetail->save();
        }
        return response()->json(['message' => 'Los promedios han sido actualizados.']);
    }

    public function acceptInvitationWorkshopDetails(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'workshop_id' => 'required|integer',
            'ruc_mype' => 'required|string',
            'dni_mype' => 'required|string',
            'email' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $workshopDetail = WorkshopDetails::where('workshop_id', $data['workshop_id'])
        ->where('ruc_mype', $data['ruc_mype'])
        ->where('dni_mype', $data['dni_mype'])
        ->first();

        if ($workshopDetail) {
            $workshopDetail->update($data);
            $message = 'Registro actualizado correctamente';
        } else {
            $workshopDetail = WorkshopDetails::create($data);
            $message = 'Registro creado correctamente';
        }

        $email = $data['email'];
        $workshop = Workshop::find($data['workshop_id']);
        $mype = Mype::where('ruc', $data['ruc_mype'])->first();

        if($data['is_participant'] == 'si') {
            AcceptInvitationWorkshopJob::dispatch($workshopDetail, $email, $workshop, $mype); 
        }
    }

    public function insertOrUpdateWorkshopDetails(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'workshop_id' => 'required|integer',
            'ruc_mype' => 'required|string',
            'dni_mype' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $testinValues = Testin::where('workshop_id', $data['workshop_id'])->first();
        $testoutValues = Testout::where('workshop_id', $data['workshop_id'])->first();

        if($data['te1']??null) {                                                           //in
            $data['te1'] = ($data['te1'] == $testinValues['question1_resp']) ? 4 : 0;
            $data['te2'] = ($data['te2'] == $testinValues['question2_resp']) ? 4 : 0;
            $data['te3'] = ($data['te3'] == $testinValues['question3_resp']) ? 4 : 0;
            $data['te4'] = ($data['te4'] == $testinValues['question4_resp']) ? 4 : 0;
            $data['te5'] = ($data['te5'] == $testinValues['question5_resp']) ? 4 : 0;

            $data['te_note'] = $data['te1'] + $data['te2'] + $data['te3'] + $data['te4'] + $data['te5'];
        }
        
        if( $data['ts1']??null) {                                                            //out
            $data['ts1'] = ($data['ts1'] == $testoutValues['question1_resp']) ? 4 : 0;
            $data['ts2'] = ($data['ts2'] == $testoutValues['question2_resp']) ? 4 : 0;
            $data['ts3'] = ($data['ts3'] == $testoutValues['question3_resp']) ? 4 : 0;
            $data['ts4'] = ($data['ts4'] == $testoutValues['question4_resp']) ? 4 : 0;
            $data['ts5'] = ($data['ts5'] == $testoutValues['question5_resp']) ? 4 : 0;

            $data['ts_note'] = $data['ts1'] + $data['ts2'] + $data['ts3'] + $data['ts4'] + $data['ts5'];
        }
   
        $workshopDetail = WorkshopDetails::where('workshop_id', $data['workshop_id'])
            ->where('ruc_mype', $data['ruc_mype'])
            ->where('dni_mype', $data['dni_mype'])
            ->first();

        if ($workshopDetail) {
            $workshopDetail->update($data);
            $message = 'Registro actualizado correctamente';
        } else {
            $workshopDetail = WorkshopDetails::create($data);
            $message = 'Registro creado correctamente';
        }

        return response()->json(['message' => $message, 'workshop_detail' => $workshopDetail], 200);
    }

    /**
     * Display the specified resource.
     */
    public function testAllQuestions($workshopId)
    {
        $workshop = Workshop::find($workshopId);

        $id_testIn = $workshop->testin_id;
        $id_testOut = $workshop->testout_id;
        
        $testin = Testin::find($id_testIn);
        $testout = Testout::find($id_testOut);

        if (!$testin && !$testout) {
            return response()->json(['error' => 'not found'], 404);
        }

        $data = [
            'q_testin_1' => $testin->question1 ?? null,
            'q_testin_2' => $testin->question2 ?? null,
            'q_testin_3' => $testin->question3 ?? null,
            'q_testin_4' => $testin->question4 ?? null,
            'q_testin_5' => $testin->question5 ?? null,

            'q_testout_1' => $testout->question1 ?? null,
            'q_testout_2' => $testout->question2 ?? null,
            'q_testout_3' => $testout->question3 ?? null,
            'q_testout_4' => $testout->question4 ?? null,
            'q_testout_5' => $testout->question5 ?? null,
        ];

        return $data;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function addPointToWorkshop($workshopId, $type)
    {
        $value = Workshop::find($workshopId);

        if(empty($value->$type)) {
            $value->$type = 0;
            $value->save();
        }

        Workshop::where('id', $workshopId)->increment($type);

        $workshop = Workshop::find($workshopId);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateWorkshopDetailsRequest $request, WorkshopDetails $workshopDetails)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WorkshopDetails $workshopDetails)
    {
        //
    }
}