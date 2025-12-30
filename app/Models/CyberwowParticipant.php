<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CyberwowParticipant extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cyberwowparticipants';

    protected $fillable = [
        'event_id',
        'ruc',
        'razonSocial',
        'nombreComercial',
        'city_id',                  // region
        'province_id',              // provincia
        'district_id',              // distrito
        'direccion',
        'economicsector_id',        // sectorEconomico
        'comercialactivity_id',     // actividadComercial
        'rubro_id',                 // rubro
        'descripcion',
        'socials',
        'typedocument_id',          // tipoDocumento
        'documentnumber',
        'lastname',
        'middlename',
        'name',
        'gender_id',                // genero
        'sick',
        'phone',
        'email',
        'birthday',
        'age',
        'country_id',               // pais
        'cargo',
        'question_1',
        'question_2',
        'question_3',
        'question_4',
        'question_5',
        'question_6',
        'question_7',
        'howKnowEvent_id',          // medioEntero
        'autorization',

        'user_id',

        'paso1',
        'poso2',
        'paso3'
    ];


    protected $casts = [
        'socials' => 'array',      // convierte JSON a array automáticamente
        'autorization' => 'boolean',
        'birthday' => 'date',
    ];



    public function region()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function provincia()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function distrito()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function sectorEconomico()
    {
        return $this->belongsTo(EconomicSector::class, 'economicsector_id');
    }

    public function actividadComercial()
    {
        return $this->belongsTo(Activity::class, 'comercialactivity_id');
    }

    public function rubro()
    {
        return $this->belongsTo(Category::class, 'rubro_id');
    }

    public function tipoDocumento()
    {
        return $this->belongsTo(Typedocument::class, 'typedocument_id');
    }

    public function genero()
    {
        return $this->belongsTo(Gender::class, 'gender_id');
    }

    public function pais()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function medioEntero()
    {
        return $this->belongsTo(PropagandaMedia::class, 'howKnowEvent_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    public function brands()
    {
        return $this->hasMany(\App\Models\CyberwowBrand::class, 'company_id', 'id');
    }
}
