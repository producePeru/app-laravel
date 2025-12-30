<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgreementCommitments extends Model
{
    use HasFactory;

    protected $table = 'agreement_commitments';

    protected $fillable = [
        'accion',
        'date',
        'modality',
        'address',
        'participants',
        'file1_path',
        'file1_name',
        'file2_path',
        'file2_name',
        'file3_path',
        'file3_name',
        'details',
        'agreement_id',
        'user_id',
        'commitment_id'
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'user_id', 'user_id');
    }

    // nuevo
    public function compromiso()
    {
        return $this->belongsTo(Commitment::class);
    }
}
