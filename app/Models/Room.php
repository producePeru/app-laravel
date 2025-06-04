<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'sala',
        'inicio',
        'fin',
        'descripcion',
        'unidad',
        'created_by',
        'updated_by'
    ];

    protected $appends = ['profile'];

    protected $dates = ['inicio', 'fin', 'deleted_at'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getProfileAttribute()
    {
        $profile = $this->creator->profile ?? null;

        if (!$profile) return null;

        return [
            'id' => $profile->id,
            'name' => $profile->name,
            'lastname' => $profile->lastname,
            'middlename' => $profile->middlename,
            'user_id' => $profile->user_id,
        ];
    }
}
