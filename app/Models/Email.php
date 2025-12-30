<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Email extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'count',
        'image',
        'description',
        'emailAccount',
        'status'
    ];
}
// php artisan make:controller Email/EmailController --api
