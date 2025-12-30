<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeCompany extends Model
{
    use HasFactory;

    protected $table = 'typecompanies';

    protected $fillable = ['name'];

}
