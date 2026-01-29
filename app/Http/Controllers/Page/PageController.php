<?php

namespace App\Http\Controllers\Page;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PageController extends Controller
{
    /**
     * Asignamos...
     */
    public function permissions(Request $request)
    {
        try {
            $user = $request->user();
            $slug = $request->query('slug');

            $page = Page::where('slug', $slug)->firstOrFail();

            $pivot = $user->pages()->where('page_id', $page->id)->first()?->pivot;

            return response()->json([
                'can_view_all'  =>  $pivot->can_view_all == 1 ? true : false,
                'can_create'    =>  $pivot->can_create == 1 ? true : false,
                'can_update'    =>  $pivot->can_update == 1 ? true : false,
                'can_delete'    =>  $pivot->can_delete == 1 ? true : false,
                'can_download'  =>  $pivot->can_download == 1 ? true : false,

                'can_finish'    =>  $pivot->can_finish == 1 ? true : false,
                'can_import'    =>  $pivot->can_import == 1 ? true : false,
                'can_download_everything' => $pivot->can_download_everything == 1 ? true : false,
                'can_date_range' => $pivot->can_date_range == 1 ? true : false,
                'can_download_reporte' => $pivot->can_download_reporte == 1 ? true : false,


            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'No tienes acceso a esta página o la página no existe.'
            ], 500);
        }
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
    public function allPages()
    {
        try {
            $pages = Page::orderBy('id', 'asc')->paginate(150);

            return response()->json([
                'success' => true,
                'data' => $pages,
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al obtener las páginas.',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    public function allPagesTypeUgo()
    {
        try {

            $pages = Page::where('office', 'UGO')->orderBy('id', 'asc')->paginate(150);

            return response()->json([
                'success' => true,
                'data' => $pages,
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al obtener las páginas.',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    public function pageToUser($id)
    {
        try {
            $user = User::findOrFail($id);

            $pages = $user->pages()->withPivot([
                'can_view_all',
                'can_create',
                'can_update',
                'can_delete',
                'can_download',
                'can_finish',
                'can_import'
            ])->get();

            // Formatear los valores del pivote
            $formatted = $pages->map(function ($page) {
                return [
                    'id' => $page->id,
                    'name' => $page->name,
                    'slug' => $page->slug,
                    'pivot' => [
                        'can_view_all' => (bool) $page->pivot->can_view_all,
                        'can_create' => (bool) $page->pivot->can_create,
                        'can_update' => (bool) $page->pivot->can_update,
                        'can_delete' => (bool) $page->pivot->can_delete,
                        'can_download' => (bool) $page->pivot->can_download,
                        'can_finish' => (bool) $page->pivot->can_finish,
                        'can_import' => (bool) $page->pivot->can_import,

                        'can_download_everything' => (bool) $page->pivot->can_download_everything,
                        'can_date_range' => (bool) $page->pivot->can_date_range,
                        'can_download_reporte' => (bool) $page->pivot->can_download_reporte,
                    ]
                ];
            });

            return response()->json([
                'status' => 200,
                'data' => $formatted
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'No tienes acceso a esta página o la página no existe.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function userAssignView(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'page_id' => 'required|exists:pages,id',
                'type' => 'required|in:can_create,can_update,can_delete,can_download,can_finish,can_import,can_view_all,can_download_everything,can_date_range,can_download_reporte',
                'value' => 'required|boolean',
            ]);

            $userId = $validated['user_id'];
            $pageId = $validated['page_id'];
            $column = $validated['type'];
            $value = $validated['value'] ? 1 : 0;

            // Buscar si ya existe la relación user-page
            $record = DB::table('page_user')
                ->where('user_id', $userId)
                ->where('page_id', $pageId)
                ->first();

            if ($record) {
                // Si existe, actualizar solo el campo específico
                DB::table('page_user')
                    ->where('user_id', $userId)
                    ->where('page_id', $pageId)
                    ->update([
                        $column => $value,
                        'updated_at' => now()
                    ]);
            } else {
                // Si no existe, insertar nuevo registro
                DB::table('page_user')->insert([
                    'user_id' => $userId,
                    'page_id' => $pageId,
                    $column => $value,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Permiso actualizado correctamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al asignar permiso.',
                'error' => $e->getMessage()
            ]);
        }
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

    public function viewsToUserSidebar()
    {
        try {

            $user_id = auth()->user()->id;

            // Buscar las páginas del usuario donde 'can_view_all' sea igual a 1
            $pages = DB::table('page_user')
                ->join('pages', 'page_user.page_id', '=', 'pages.id')
                ->where('page_user.user_id', $user_id)
                ->where('page_user.can_view_all', 1)
                ->select('pages.id', 'pages.slug')
                ->get();

            if ($pages->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No hay páginas disponibles para este usuario.'
                ]);
            }

            return response()->json([
                'status' => 200,
                'data' => $pages
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al obtener las vistas del usuario.',
                'error' => $e->getMessage()
            ]);
        }
    }
}
