<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AdminImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'name',
        'path',
        'size',

        'created_by',
        'updated_by',
    ];


    public function admin()
    {
        return $this->belongsTo("App\Models\Admin");
    }

    public function getFileSize() 
    {
        return Storage::size($this->path);
    }
}
