<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commitment extends Model
{
    use HasFactory;

    // protected $table = 'agreements_commitments'; commitments
    protected $table = 'commitments';

    protected $fillable = [
        'title',
        'type',
        'description',
        'meta',
        'agreement_id',
        'user_id'
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'user_id', 'user_id');
    }

    public function commitments()
    {
        return $this->hasMany(AgreementCommitments::class, 'commitment_id');
    }

    // nuevo
    public function acciones()
    {
        return $this->hasMany(AgreementCommitments::class);
    }

    public function evento()
    {
        return $this->belongsTo(Agreement::class);
    }


}
