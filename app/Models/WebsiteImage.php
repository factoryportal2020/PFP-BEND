<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class WebsiteImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'website_id',
        'name',
        'path',
        'type', //['banner','about','feature']
        'title', 
        'caption', 
        'detail', 
        'size',

        'created_by',
        'updated_by',
    ];


    public function website()
    {
        return $this->belongsTo("App\Models\Website");
    }

    public function getFileSize() 
    {
        return Storage::size($this->path);
    }
}
