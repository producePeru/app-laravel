<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code_iso'];

    //1 to 1
    public function user()
    {
        return $this->hasOne(User::class);
    }
}
