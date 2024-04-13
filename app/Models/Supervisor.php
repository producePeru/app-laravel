<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supervisor extends Model
{
    use HasFactory;

    protected $table = 'supervisores';

    protected $guarded = ['id'];

    protected $fillable = ['user_id'];

    protected $hidden = ['created_at', 'updated_at'];

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }
    public function profile()
    {
        return $this->hasOne(Profile::class, 'user_id', 'user_id');
    }
    public function cde()
    {
        return $this->hasOne('App\Models\Cde', 'id', 'id');
    }

    public function office()
    {
        return $this->hasOneThrough(Office::class, Profile::class, 'user_id', 'id', 'user_id', 'office_id');
    }

    public function scopeWithProfileAndRelations($query)
    {
        return $query->with(['profile', 'cde', 'office', 'user'])
        ->orderBy('created_at', 'desc')
        ->paginate(20);
    }
}
