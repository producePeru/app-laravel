<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Themecomponent extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = ['component_id', 'created_at', 'updated_at'];

    public function componet()
    {
        return $this->belongsTo('App\Models\Componet');
    }

    public function advisory()
    {
        return $this->belongsTo('App\Models\Advisory');
    }
}
