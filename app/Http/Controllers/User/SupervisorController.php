<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Supervisor;

class SupervisorController extends Controller
{
    public function index()
    {

        $supervisor = Supervisor::withProfileAndRelations();

        return response()->json($supervisor, 200);

    }
}
