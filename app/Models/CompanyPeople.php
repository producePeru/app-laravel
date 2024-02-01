<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyPeople extends Model
{
    use HasFactory;

    protected $table = 'company_people';

    protected $fillable = [
        'ruc',
        'number_document'
    ];
}
