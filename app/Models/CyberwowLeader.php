<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CyberwowLeader extends Model
{
    use HasFactory;

    protected $table = 'cyberwowleader';

    protected $fillable = [
        'user_id',
        'wow_id',
        'supervisor',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
