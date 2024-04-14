<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ItemImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'name',
        'path',
        'type',
        'size',

        'created_by',
        'updated_by',
    ];


    public function item()
    {
        return $this->belongsTo("App\Models\Item");
    }

    public function getFileSize() 
    {
        // Ensure $this->file_path begins with '/public/';
        return Storage::size($this->path);
    }

}
