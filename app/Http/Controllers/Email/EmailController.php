<?php

namespace App\Http\Controllers\Email;

use App\Http\Controllers\Controller;
use App\Models\Email;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EmailController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Email::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'count' => 'nullable|integer',
            'image' => 'nullable|url',
            'description' => 'nullable|string|max:1000',
            'emailAccount' => 'nullable|email|max:255',
        ]);

        Email::create($validated);
        return response()->json(['status' => 200, 'message' => 'Creado']);
    }

    /**
     * Display the specified resource.
     */
    public function show($type, $emailAccount)
    {
        $emails = Email::where([
                'type' => $type,
                'emailAccount' => $emailAccount
            ])
            ->where('status', '1')
            ->get();

        return response()->json([
            'emailAccount'   => $emailAccount,
            'total_records'  => $emails->count(),
            'total_count'    => $emails->sum('count'),
            'data'           => $emails
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $email = Email::findOrFail($id);

        $validated = $request->validate([
            'count' => 'nullable|integer',
            'image' => 'nullable|url',
            'description' => 'nullable|string|max:1000',
            'emailAccount' => 'nullable|email|max:255',
        ]);

        $email->update($validated);
        return response()->json($email);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $email = Email::findOrFail($id);
        $email->delete();
        return response()->json(['message' => 'Deleted'], 204);
    }
}
