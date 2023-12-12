<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'path',

        'created_by',
        'updated_by',
    ];


    public function category()
    {
        return $this->belongsTo("App\Models\Category");
    }
}
