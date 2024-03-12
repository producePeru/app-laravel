<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Formalization extends Model
{
    protected $table = null;

    public function formalization20()
    {
        return $this->hasOne(Formalization20::class, 'id', 'id');
    }

    public function formalization10()
    {
        return $this->hasOne(Formalization10::class, 'id', 'id');
    }
}

class Formalization20 extends Model
{
    protected $table = 'formalization20';

    public function formalization()
    {
        return $this->belongsTo(Formalization::class, 'id', 'id');
    }
}

class Formalization10 extends Model
{
    protected $table = 'formalization10';

    public function formalization()
    {
        return $this->belongsTo(Formalization::class, 'id', 'id');
    }
}
