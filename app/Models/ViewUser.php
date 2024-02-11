<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViewUser extends Model
{
    use HasFactory;

    protected $table = 'view_user';

    protected $fillable = ['id_user', 'id_view'];

    public function views()
    {
        return $this->belongsTo(View::class, 'id');
    }

}
