<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory, SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $table = 'questions';

    protected $fillable = [
        'tableName',
        'label',
        'type',
        'model',
        'required',
        'position'
    ];

    public function options()
    {
        return $this->hasMany(QuestionOption::class);
    }
}
