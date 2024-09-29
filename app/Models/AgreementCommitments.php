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
        'id_agreement',
        // 'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
