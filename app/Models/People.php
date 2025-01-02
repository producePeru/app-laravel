<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class People extends Model
{
    use HasFactory;
    use SoftDeletes;

    // protected $guarded = ['id'];

    protected $fillable = [
        'documentnumber',
        'lastname',
        'middlename',
        'name',
        'phone',
        'email',
        'birthday',
        'sick',
        'hasSoon',
        'country_id',
        'city_id',
        'province_id',
        'district_id',
        'address',
        'typedocument_id',
        'gender_id',
        'user_id'
    ];

    protected $dates = ['deleted_at'];

    public function pais()
    {
        return $this->belongsTo(Country::class, 'country_id'); // Asegúrate de que 'country_id' es el nombre correcto de la columna en 'people'
    }

    public function city()
    {
        return $this->belongsTo('App\Models\City');
    }

    public function province()
    {
        return $this->belongsTo('App\Models\Province');
    }

    public function district()
    {
        return $this->belongsTo('App\Models\District');
    }

    public function gender()
    {
        return $this->belongsTo('App\Models\Gender');
    }

    public function user()
    {
        return $this->belongsToMany(User::class);
    }

    public function from()
    {
        return $this->belongsToMany(From::class);
    }

    public function mype()
    {
        return $this->belongsToMany(Mype::class);
    }

    public function typedocument()
    {
        return $this->belongsTo('App\Models\Typedocument');
    }

    public function advisory()
    {
        return $this->hasMany('App\Models\Advisory');
    }

    public function formalization10()
    {
        return $this->belongsTo('App\Models\Formalization10');
    }

    public function formalization20()
    {
        return $this->belongsTo('App\Models\Formalization20');
    }

    public function profile()
    {
        return $this->belongsTo('App\Models\Profile');
    }

    public function idadvisory()
    {
        return $this->hasMany('App\Models\Advisory', 'people_id');
    }

    public function idformalization10()
    {
        return $this->hasMany('App\Models\Formalization10', 'people_id');
    }

    public function idformalization20()
    {
        return $this->hasMany('App\Models\Formalization20', 'people_id');
    }

    public function genderpeople()
    {
        return $this->belongsTo(Gender::class, 'id');
    }

    public function formalizationDigital()
    {
        return $this->hasOne('App\Models\FormalizationDigital', 'documentnumber', 'documentnumber');
    }


    public function scopeWithProfileAndRelations($query, $filters)       //super
    {
        // return $query->with(['city', 'province', 'district', 'gender', 'typedocument', 'from', 'user.profile'])
        // ->orderBy('created_at', 'desc')
        // ->paginate(20);

        $query = $query->with(
            ['city', 'province', 'district', 'gender', 'typedocument', 'from', 'user.profile']
        )->orderBy('created_at', 'desc');

        if ($filters['search'] !== null) {
            $query->where('documentnumber', $filters['search'])
                ->orWhere('lastname', 'LIKE', $filters['search']. '%')
                ->orWhere('middlename', 'LIKE', $filters['search']. '%')
                ->orWhere('name', 'LIKE', $filters['search']. '%');
        }

        return $query->paginate(100);
    }

    public function scopeWithProfileAndUser($query, $userId, $filters)        //asesores
    {
        $query->with(['city', 'province', 'district', 'gender', 'typedocument', 'from', 'user.profile'])
        ->whereHas('user', function ($q) use ($userId) {
            $q->where('users.id', $userId);
        })
        ->orderBy('created_at', 'desc');

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('documentnumber', $filters['search'])
                ->orWhere('lastname', 'LIKE', $filters['search'] . '%')
                ->orWhere('middlename', 'LIKE', $filters['search'] . '%')
                ->orWhere('name', 'LIKE', $filters['search'] . '%');
        });
    }

    return $query->paginate(100);
    }

    protected static function booted()
    {
        static::deleting(function ($user) {
            $user->from()->detach();
            $user->user()->detach();
        });
    }

    public static function search($searchTerm)
    {
        return self::where('documentnumber', 'like', "%{$searchTerm}%")
                    ->orWhere('lastname', 'like', "%{$searchTerm}%")
                    ->orWhere('middlename', 'like', "%{$searchTerm}%")
                    ->orWhere('name', 'like', "%{$searchTerm}%")
                    ->get();
    }
}
