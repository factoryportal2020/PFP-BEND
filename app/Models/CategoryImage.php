<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CategoryImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'path',
        'size',

        'created_by',
        'updated_by',
    ];


    public function category()
    {
        return $this->belongsTo("App\Models\Category");
    }

    public function getFileSize() 
    {
        return Storage::size($this->path);
    }
}
