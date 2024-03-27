<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class People extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_type',
        'number_document',
        'last_name',
        'middle_name',
        'name',
        'department',
        'province',
        'district',
        'address',
        'email',
        'phone',
        'birthdate',
        'gender',
        'lession',
        'created_by',
        'update_by',
    ];

    public function departament()
    {
        return $this->belongsTo(Departament::class, 'department');
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district');
    }

    public function postPerson()
    {
        return $this->hasOne(Post_Person::class, 'number_document', 'dni_people');
    }
    public function userPhoto()
    {
        return $this->hasOne(PersonPhoto::class, 'dni', 'number_document');
    }
}
