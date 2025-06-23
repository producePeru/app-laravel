<?php

namespace App\Http\Controllers\Page;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * Asignamos...
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $slug = $request->query('slug');

        $page = Page::where('slug', $slug)->firstOrFail();

        $pivot = $user->pages()->where('page_id', $page->id)->first()?->pivot;

        return response()->json([
            'can_create' => $pivot->can_create ?? false,
            'can_update' => $pivot->can_update ?? false,
            'can_delete' => $pivot->can_delete ?? false,
            'can_download' => $pivot->can_download ?? false,
        ]);
    }

    // Creamos una nueva página
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:pages',
        ]);

        Page::create([
            'name' => $validated['name']
        ]);

        return response()->json([
            'message' => 'Página creada correctamente',
            'status' => 200,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
    public function destroy($id)
    {
        $page = Page::findOrFail($id);
        $page->delete();

        return response()->json(['message' => 'Página eliminada']);
    }
}
