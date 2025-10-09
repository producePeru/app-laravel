<?php

namespace App\Http\Controllers\Email;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $templates = EmailTemplate::select('id', 'name', 'content')->get();

        return response()->json([
            'status' => 200,
            'message' => 'Plantillas cargadas correctamente',
            'data' => $templates
        ], 200);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $template = EmailTemplate::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Plantilla creada correctamente',
            'data' => $template,
            'status' => 200
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function showSelect()
    {
        $templates = EmailTemplate::select('id', 'name', 'content')
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'name' => $t->name,
                    'content' => $t->content,
                    'label' => $t->name,  // Para el select
                    'value' => $t->id     // Para el select
                ];
            });

        return response()->json([
            'status' => 200,
            'message' => 'Plantillas cargadas correctamente',
            'data' => $templates
        ], 200);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
