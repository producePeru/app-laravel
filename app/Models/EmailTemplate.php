<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmailTemplate extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $table = 'email_templates';

    protected $fillable = [
        'name',
        'content',
    ];
}
