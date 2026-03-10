<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    protected static function booted()
    {
        static::creating(function ($page) {
            if (empty($page->slug)) {
                // Opción 1: usando Str
                $page->slug = Str::slug($page->name);

                // Opción 2: usando Cocur (más fino)
                // $slugify = new Slugify();
                // $page->slug = $slugify->slugify($page->name);
            }
        });
    }

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot([
                'can_view_all',
                'can_create',
                'can_update',
                'can_delete',
                'can_download',
                'can_finish',
                'can_import',
                'can_download_everything',
                'can_date_range',
                'can_download_reporte',
            ])->withTimestamps();
    }
}
