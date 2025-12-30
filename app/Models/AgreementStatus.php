<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgreementStatus extends Model
{
    protected $table = 'agreementstatus';

    protected $fillable = ['name', 'status'];

    protected $hidden = ['created_at', 'updated_at'];

    public function convenios()
    {
        return $this->hasMany(Agreement::class, 'id');
    }
}
