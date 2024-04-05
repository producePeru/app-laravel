<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailProcedure extends Model
{
    use HasFactory;

    protected $table = 'detailprocedures';

    protected $guarded = ['id'];

    protected $hidden = ['created_at', 'updated_at'];

    public function formalization10()
    {
        return $this->hasMany('App\Models\Formalization10');
    }
}
