<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgreementPDF extends Model
{
    use HasFactory;

    protected $table = 'pdf_agreements';

    protected $fillable = ['created', 'name', 'status', 'path'];
}
