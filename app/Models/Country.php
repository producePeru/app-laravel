<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    // protected $table = 'users';

    protected $fillable = ['iso', 'name', 'status'];

    //1 to 1
    public function user()
    {
        return $this->hasOne(User::class);
    }
}
