<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdviserSupervisor extends Model
{
    use HasFactory;

    protected $table = 'adviser_supervisor';

    protected $fillable = [
        'id_adviser',
        'id_supervisor',
        'created_by'
    ];
}
