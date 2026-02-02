<?php

namespace App\Http\Controllers\ComprasMiPeru;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CpRegistros;
use App\Models\Mype;

class ComprasPeruController extends Controller
{

  public function store(Request $request)
  {
    $user = Auth::user();

    $validated = $request->validate([
      'city_id' => 'required|exists:cities,id',
      'province_id' => 'required|exists:provinces,id',
      'district_id' => 'required|exists:districts,id',
      'economicsector_id' => 'required|exists:cp_sector_priorizado,id',
      'comercialactivity_id' => 'required|exists:comercialactivities,id',
      'component_id' => 'required|exists:cp_componentes,id',
      'theme_id' => 'required|exists:cp_temas,id',
      'modality_id' => 'required|exists:modalities,id',
      'ruc' => [
        'required',
        'digits:11',
        function ($attribute, $value, $fail) {
          if (!in_array(substr($value, 0, 2), ['10', '15', '17', '20'])) {
            $fail('El RUC debe empezar con 10, 15, 17 o 20');
          }
        }
      ],
      'razonSocial' => 'required|string|max:255',
      'periodo' => 'nullable|string|max:255',
      'cantidad' => 'nullable|integer|min:1',
      'ubicacion' => 'nullable|string|max:255',
      'people_id' => 'required|integer',
      'ruc_obs' => 'nullable|in:1',
      'cde_id' => 'required|exists:cdes,id',
    ]);

    $registro = CpRegistros::create([
      'city_id' => $validated['city_id'],
      'province_id' => $validated['province_id'],
      'district_id' => $validated['district_id'],

      'economicsector_id' => $validated['economicsector_id'],
      'comercialactivity_id' => $validated['comercialactivity_id'],

      'component_id' => $validated['component_id'],
      'theme_id' => $validated['theme_id'],
      'modality_id' => $validated['modality_id'],

      'ruc' => $validated['ruc'],
      'razonSocial' => $validated['razonSocial'],

      'periodo' => $validated['periodo'] ?? null,
      'cantidad' => $validated['cantidad'] ?? null,
      'ubicacion' => $validated['ubicacion'] ?? null,

      'people_id' => $validated['people_id'],
      'ruc_obs' => $validated['ruc_obs'] ?? null,
      'user_id' => $user->id,
      'cde_id' => $validated['cde_id'],
    ]);

    return response()->json([
      'message' => 'Registro creado correctamente',
      'data' => $registro,
      'status' => 200
    ]);
  }

  public function updateDataMype(Request $request)
  {
    $request->validate([
      'ruc' => 'required|size:11',
      'socialReason' => 'required|string|max:255',
    ]);

    $mype = Mype::updateOrCreate(
      ['ruc' => $request->ruc],
      ['socialReason' => $request->socialReason]
    );

    return response()->json([
      'status' => 200,
      'message' => 'MYPE registrada o actualizada correctamente',
      'data' => $mype
    ], 200);
  }
}
