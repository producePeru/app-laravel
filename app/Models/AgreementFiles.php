<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class AgreementFiles extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'filesagreements';

    protected $fillable = ['name', 'path', 'agreements_id'];

    protected $dates = ['deleted_at'];

    public function convenio()
    {
        return $this->belongsTo(Agreement::class, 'agreements_id');
    }
}
