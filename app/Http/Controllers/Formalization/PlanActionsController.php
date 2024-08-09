<?php

namespace App\Http\Controllers\Formalization;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Advisory;
use App\Models\Formalization10;
use App\Models\Formalization20;
use Illuminate\Support\Facades\DB;

class PlanActionsController extends Controller
{


    public function planActions()
    {
        $advisories = DB::table('advisories')
        ->whereNotNull('ruc')
        ->whereRaw('LENGTH(ruc) = 11')
        ->select('ruc')
        ->distinct()
        ->get();

        $formattedResults = $advisories->map(function ($item) {
            return [
                'name' => null,
                'ruc' => $item->ruc,
            ];
        });

        $response = [
            'data' => $formattedResults
        ];

        return response()->json($response);
    }


    public function rucFormalizationR20Set()
    {
        $advisories = DB::table('formalizations20')
        ->whereNotNull('ruc')
        ->whereRaw('LENGTH(ruc) = 11')
        ->select('ruc', 'user_id', 'nameMype')
        ->distinct()
        ->get();


        $existingRucs = DB::table('mypes')
            ->pluck('ruc')
            ->toArray();

        $newRucs = $advisories->filter(function ($item) use ($existingRucs) {
            return !in_array($item->ruc, $existingRucs);
        });

        $dataToInsert = $newRucs->map(function ($item) {
            return [
                'name' => $item->nameMype,
                'ruc' => $item->ruc,
                'user_id' => $item->user_id
            ];
        })->toArray();

        if (!empty($dataToInsert)) {
            DB::table('mypes')->insert($dataToInsert);
        }

        $response = [
            'data' => $dataToInsert
        ];

        return response()->json(['message' => 'success']);
    }


    public function rucFormalizationR10Set()
    {
        $advisories = DB::table('formalizations10')
        ->whereNotNull('ruc')
        ->whereRaw('LENGTH(ruc) = 11')
        ->select('ruc', 'user_id')
        ->distinct()
        ->get();


        $existingRucs = DB::table('mypes')
            ->pluck('ruc')
            ->toArray();

        $newRucs = $advisories->filter(function ($item) use ($existingRucs) {
            return !in_array($item->ruc, $existingRucs);
        });

        $dataToInsert = $newRucs->map(function ($item) {
            return [
                'name' => null,
                'ruc' => $item->ruc,
                'user_id' => $item->user_id
            ];
        })->toArray();

        if (!empty($dataToInsert)) {
            DB::table('mypes')->insert($dataToInsert);
        }

        $response = [
            'data' => $dataToInsert
        ];

        return response()->json(['message' => 'success']);
    }


    public function rucAdvisoriesSet()
    {
        $advisories = DB::table('advisories')
        ->whereNotNull('ruc')
        ->whereRaw('LENGTH(ruc) = 11')
        ->select('ruc', 'user_id')
        ->distinct()
        ->get();

        $existingRucs = DB::table('mypes')
            ->pluck('ruc')
            ->toArray();

        $newRucs = $advisories->filter(function ($item) use ($existingRucs) {
            return !in_array($item->ruc, $existingRucs);
        });

        $dataToInsert = $newRucs->map(function ($item) {
            return [
                'name' => null,
                'ruc' => $item->ruc,
                'user_id' => $item->user_id
            ];
        })->toArray();

        if (!empty($dataToInsert)) {
            DB::table('mypes')->insert($dataToInsert);
        }

        $response = [
            'data' => $dataToInsert
        ];

        return response()->json(['message' => 'success']);
    }



















}
