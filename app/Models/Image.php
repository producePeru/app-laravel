<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;

class Image extends Model
{
    use HasFactory;

    // use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'name',
        'url',
        'mime_type',
        'size',
        'from_origin',
        'id_origin'
    ];
}
