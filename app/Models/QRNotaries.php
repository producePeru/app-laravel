<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QRNotaries extends Model
{
    use HasFactory;

    protected $table = 'qrnotaries';

    protected $fillable = [
        'typedocument_id',
        'documentnumber',
        'nationality',
        'name',
        'lastname',
        'middlename',
        'phone',
        'email',
        'economicsector_id',
        'motivo',
        'notary',
        'califica'
    ];
}
