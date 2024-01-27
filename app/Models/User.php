<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Ramsey\Uuid\Uuid;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // 'id',
        'nick_name',
        'password',
        'document_type',
        'document_number',
        'last_name',
        'middle_name',
        'name',
        'country_code',
        'birthdate',
        'gender',
        'is_disabled',
        'email',
        'phone_number',
        'office_code',
        'sede_code',
        'role',
        'created_by'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed'
    ];

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_code');
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class, 'sede_code');
    }
}
