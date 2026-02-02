<?php

namespace App\Http\Controllers\ComprasMiPeru;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CpRegistros;
use App\Models\Mype;
use Carbon\Carbon;

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


  public function index(Request $request)
  {
    $perPage = $request->get('per_page', 100); // default 10

    $items = CpRegistros::query()
      ->with([
        'city:id,name',
        'province:id,name',
        'district:id,name',
        'economicsectors:id,name',
        'comercialactivity:id,name',
        'component:id,name',
        'theme:id,name',
        'modality:id,name',

        'people:id,name,lastname,middlename,email,phone,typedocument_id,documentnumber,country_id,birthday,gender_id,sick,phone,email',
        'people.typedocument:id,avr',
        'people.pais:id,name',
        'people.gender:id,name',

        'asesor:id,name,lastname,middlename,email,phone',
        'cde:id,city,province,district',
      ])
      ->orderBy('created_at', 'ASC')
      ->paginate($perPage)
      ->through(function ($item) {
        return $this->mapItems($item);
      });

    return response()->json([
      'data' => $items
    ], 200);
  }


  private function mapItems($item)
  {
    $people = $item->people;
    $cde    = $item->cde;
    $asesor = $item->asesor;
    $sector = $item->economicsectors;

    // Helper local para mayúsculas seguras
    $upper = fn($value) => $value ? mb_strtoupper($value, 'UTF-8') : null;

    return [
      'id'          => $item->id,
      'ruc'         => $item->ruc,
      'razonSocial' => $upper($item->razonSocial),
      'sector_priorizado' => $upper($sector?->name),
      'region_asesor'   => $upper($cde?->city),
      'province_asesor' => $upper($cde?->province),
      'district_asesor' => $upper($cde?->district),
      'asesor' => $asesor ? $upper(trim("{$asesor->name} {$asesor->lastname} {$asesor->middlename}")) : null,
      'tipo_documento' => $people?->typedocument?->avr,
      'documentnumber' => $people?->documentnumber,
      'country'        => $people?->pais?->name,
      'birthday' => $people?->birthday ? Carbon::parse($people->birthday)->format('d/m/Y') : null,
      'lastname'   => $upper($people?->lastname),
      'middlename' => $upper($people?->middlename),
      'name'       => $upper($people?->name),
      'gender'      => $people->gender->name,
      'sick'      => $people->sick,
      'phone' => $people->phone,
      'email' => $people->email,
      'periodo' => $item->periodo,
      'cantidad' => $item->cantidad,
      'mype_region' => $item->city->name,
      'mype_province' => $item->province->name,
      'mype_district' => $item->district->name,
      'mype_ubicacion' => $item->ubicacion,
      'created_at' => Carbon::parse($item->created_at)->format('d/m/Y'),
      'componente' => $item->component->name,
      'tema' => $item->theme->name,
      'modalidad' => $item->modality->name,
      'mes' =>  mb_strtoupper(Carbon::parse($people->created_at)->locale('es')->translatedFormat('M'), 'UTF-8'),
      'mes_numero' => Carbon::parse($people->created_at)->format('n'),
      'comercialactivity' => $item->comercialactivity->name,
      'year' => Carbon::parse($item->created_at)->format('Y')
    ];
  }
}
