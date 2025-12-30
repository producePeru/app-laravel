<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class From extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = ['pivot', 'created_at', 'updated_at'];

    public function people()
    {
        return $this->belongsToMany(People::class);
    }
}
