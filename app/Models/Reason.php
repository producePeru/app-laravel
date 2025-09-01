<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reason extends Model
{
    use HasFactory;

    protected $fillable = [
        'table_name',
        'row_id',
        'description',
        'action',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $appends = ['created_at_formatted'];

    public function getCreatedAtFormattedAttribute()
    {
        return $this->created_at
            ? $this->created_at->format('d/m/Y H:i:s')
            : null;
    }
}
