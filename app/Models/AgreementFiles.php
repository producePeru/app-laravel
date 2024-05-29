<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgreementFiles extends Model
{
    use HasFactory;

    protected $table = 'filesagreements';

    protected $fillable = ['name', 'path', 'agreements_id'];

    public function convenio()
    {
        return $this->belongsTo(Agreement::class, 'agreements_id');
    }
}
