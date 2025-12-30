<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleCompany extends Model
{
    use HasFactory;

    protected $table = 'role_company';

    protected $fillable = [
        'name'
    ];
}
