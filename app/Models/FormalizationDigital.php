<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class FormalizationDigital extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'formalizationdigital';

    protected $guarded = ['id'];

    protected $dates = ['deleted_at'];

    public function people()
    {
        return $this->belongsTo('App\Models\People', 'documentnumber', 'documentnumber');
    }

    public function city()
    {
        return $this->belongsTo('App\Models\City');
    }

    public function province()
    {
        return $this->belongsTo('App\Models\Province');
    }

    public function district()
    {
        return $this->belongsTo('App\Models\District');
    }

    public function cde()
    {
        return $this->belongsTo('App\Models\Cde');
    }
}
