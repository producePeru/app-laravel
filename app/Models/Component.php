<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Component extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = ['created_at', 'updated_at'];

    public function advisory()
    {
        return $this->belongsTo('App\Models\Advisory');
    }

    public function theme()
    {
        return $this->hasOne('App\Models\ThemeComponent');
    }
}
