<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CustomerImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'name',
        'path',
        'size',

        'created_by',
        'updated_by',
    ];


    public function customer()
    {
        return $this->belongsTo("App\Models\Customer");
    }

    public function getFileSize() 
    {
        return Storage::size($this->path);
    }
}
