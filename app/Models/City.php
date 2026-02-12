<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class City extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = ['created_at', 'updated_at'];

    public function people()
    {
        return $this->hasMany('App\Models\People');
    }

    public function profile()
    {
        return $this->hasMany('App\Models\Profile');
    }

    public function advisory()
    {
        return $this->hasMany('App\Models\Advisory');
    }

    public function formalization10()
    {
        return $this->hasMany('App\Models\Formalization10');
    }

    public function formalization20()
    {
        return $this->hasMany('App\Models\Formalization20');
    }

    public function convenios()
    {
        return $this->hasMany('App\Models\Agreement');
    }



    // 👇 Relación
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'city_id');
    }

    // 👇 FUNCIÓN PRINCIPAL
    public static function numberEventsByRegion(?int $year = null)
    {
        return self::select('id as id_region', 'name as region')
            ->where('id', '!=', 26) // 👈 excluir región id 26
            ->withCount([
                'attendances as cantidad_eventos' => function ($query) use ($year) {
                    if ($year) {
                        $query->whereYear('startDate', $year);
                    }
                }
            ])
            ->orderByDesc('cantidad_eventos')
            ->get();
    }
}
