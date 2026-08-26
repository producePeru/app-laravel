<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailCancelado extends Model
{
    use SoftDeletes;

    protected $table = 'email_cancelados';

    protected $fillable = [
        'email',
        'motivo',
    ];
}
