<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgreementActions extends Model
{
    use HasFactory;

    protected $table = 'actionsagreements';

    protected $fillable = ['description', 'agreements_id'];

    public function convenio()
    {
        return $this->belongsTo(Agreement::class, 'id');
    }

}
