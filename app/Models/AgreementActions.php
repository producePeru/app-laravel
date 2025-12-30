<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgreementActions extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'actionsagreements';

    protected $dates = ['deleted_at'];

    protected $fillable = ['description', 'agreements_id'];

    public function convenio()
    {
        return $this->belongsTo(Agreement::class, 'id');
    }

}
