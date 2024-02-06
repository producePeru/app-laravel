<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Drive extends Model
{
    use HasFactory;

    protected $fillable = [ 'created_by', 'name', 'path', 'status' ];

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

}
