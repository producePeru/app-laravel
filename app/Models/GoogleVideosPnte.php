<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoogleVideosPnte extends Model
{
    use HasFactory;

    protected $table = 'videospntegoogle';

    protected $fillable = [
        'user_id',
        'google_file_id',
        'file_name',
        'file_type',
        'file_size',
        'web_view_link',
        'web_content_link',
        'title',
        'description'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
