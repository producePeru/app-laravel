<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class View extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = ['id', 'user_id', 'created_at', 'updated_at'];

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function getDecodedViewsAttribute()
    {
        return json_decode($this->attributes['views'], true);
    }
}
