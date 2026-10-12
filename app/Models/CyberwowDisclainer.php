<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CyberwowDisclainer extends Model
{
    use HasFactory;

    protected $table = 'cyberwow_disclainer';

    protected $fillable = [
        'descripcion',
        'id_fair',
        'user_id',
    ];

    public function fair()
    {
        return $this->belongsTo(Fair::class, 'id_fair');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
