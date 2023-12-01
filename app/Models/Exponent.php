<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_type',
        'document_number',
        'first_name',
        'last_name',
        'middle_name',
        'gender',
        'email',
        'ruc_number',
        'phone_number',
        'specialty',
        'profession',
        'cv_link',

        'user_id'
    ];

    public function workshop()
    {
        return $this->hasOne(Workshop::class);
    }
}
