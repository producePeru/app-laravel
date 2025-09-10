<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Digitalroute extends Model
{
    use HasFactory;

    protected $table = 'digitalroutes';

    protected $fillable = [
        'ruc',
        'dni',
        'person_id',
        'mype_id',
        'user_id',
        'status',
        'comments'
    ];

    public function person()
    {
        return $this->belongsTo(People::class, 'person_id');
    }

    public function mype()
    {
        return $this->belongsTo(Mype::class, 'mype_id');
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'user_id', 'user_id');   // 1modelo 2conexion
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function asesor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeSearch($query, $search)
    {
        if ($search) {
            $query->where(function ($q) use ($search) {
                // Define solo las columnas y relaciones que quieres filtrar
                $q->where('id', 'like', "%$search%") // Filtrar por ID del modelo principal
                    ->orWhereHas('profile', function ($q) use ($search) {
                        $q->where('documentnumber', 'like', "%$search%"); // Buscar por documento del asesor
                    })
                    ->orWhereHas('person', function ($q) use ($search) {
                        $q->where('documentnumber', 'like', "%$search%") // Buscar por documento de la persona
                            ->orWhere('name', 'like', "%$search%") // Buscar por nombre de la persona
                            ->orWhere('lastname', 'like', "%$search%"); // Buscar por apellido de la persona
                    })
                    ->orWhereHas('mype', function ($q) use ($search) {
                        $q->where('ruc', 'like', "%$search%") // Filtrar por RUC
                            ->orWhere('address', 'like', "%$search%"); // Filtrar por dirección
                    });
            });
        }
    }

    public function scopeWithFormalizationRangeDate($query, $filters)
    {
        $query->with([
            'modality',
            'people.gender:id,name',
            'people.typedocument:id,avr',
            'supervisor.supervisorUser.profile',
            'user.profile',
            'supervisado.supervisadoUser.profile',
            'supervisado.supervisadoUser.profile.cde:id,name',
            'mype:id,name,ruc',
            'comercialactivity',
            'regime',
            'notary:id,name,price',
            'economicsector',
            'city',
            'province',
            'district'
        ])->orderBy('created_at', 'desc');

        if (!empty($filters['asesor'])) {
            $query->where('user_id', $filters['asesor']);
        }

        if (!empty($filters['name'])) {
            $query->whereHas('people', function ($q) use ($filters) {
                $q->where('documentnumber', 'like', '%' . $filters['name'] . '%');
            });
        }

        if (!empty($filters['dateStart']) && !empty($filters['dateEnd'])) {
            $endDate = date('Y-m-d', strtotime($filters['dateEnd'] . ' +1 day'));
            $query->whereBetween('created_at', [$filters['dateStart'], $endDate]);
        }

        if (!empty($filters['year'])) {
            $query->whereYear('created_at', $filters['year']);
        }
    }
}
