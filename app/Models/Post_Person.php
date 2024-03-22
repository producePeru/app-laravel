<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post_Person extends Model
{
    use HasFactory;

    protected $table = 'post_person';

    protected $fillable = [
        'dni_people',
        'id_post',
        'status'
    ];

    public function people()
    {
        return $this->belongsTo(People::class, 'dni_people', 'number_document');
    }
    public function digital()
    {
        return $this->belongsTo(FormalizationDigital::class, 'dni_people', 'dni_person');
    }
}
