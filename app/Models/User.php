<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // 'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'pivot',
        'email_verified_at',
        'password',
        'remember_token',
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function views()
    {
        return $this->hasMany('App\Models\View');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function people()
    {
        return $this->belongsToMany(People::class);
    }

    public function advisory()
    {
        return $this->hasOne(Advisory::class);
    }

    public function formalization10()
    {
        return $this->hasOne(Formalization10::class);
    }

    public function formalization20()
    {
        return $this->hasOne(Formalization20::class);
    }

    public function notary()
    {
        return $this->hasOne('App\Models\Notary');
    }

    // public function supervisor()
    // {
    //     return $this->hasMany('App\Models\Supervisor', 'user_id');
    // }

    protected static function booted()
    {
        static::deleting(function ($user) {
            $user->profile()->delete();
            $user->roles()->detach();
        });
    }

    public function scopeWithProfileAndRelations($query)
    {
        return $query->with(['profile', 'profile.office', 'profile.cde'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }
}
