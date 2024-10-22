<?php

namespace App\Http\Controllers\Fair;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Fair;
use Illuminate\Support\Str;

class FairController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $query = Fair::with([
            'region',
            'provincia',
            'distrito',
            'profile:id,user_id,name,lastname,middlename'
        ])->search($search)
        ->orderBy('created_at', 'desc');

        $data = $query->paginate(50);

        $data->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'slug' => $item->slug,
                'title' => $item->title,
                'description' => $item->description,
                'metaMypes' => $item->metaMypes,
                'metaSales' => $item->metaSales,
                'startDate' => $item->startDate,
                'endDate' => $item->endDate,
                'modality' => $item->modality,
                'city' => $item->region->name,
                'province' => $item->provincia->name,
                'district' => $item->distrito->name,
                'profile' => $item->profile->name.' '. $item->profile->lastname.' '. $item->profile->middlename,
            ];
        });

        return response()->json(['data' => $data]);

    }


    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $user_role = getUserRole();
        $user_id = $user_role['user_id'];

        $data = $request->all();

        $slug = Str::slug($data['title']);

        $originalSlug = $slug;

        $count = 1;

        while (Fair::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        $data['slug'] = $slug;
        $data['user_id'] = $user_id;

        Fair::create($data);

        return response()->json(['message' => 'Feria creada con éxito', 'status' => 200]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
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
    public function destroy(string $id)
    {
        //
    }
}
