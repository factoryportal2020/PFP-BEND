<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TaskImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'name',
        'path',
        'type', //['main','initial','working','completed','delivered']
        'extension', //['image','pdf','doc','xls']
        'size',

        'created_by',
        'updated_by',
    ];


    public function task()
    {
        return $this->belongsTo("App\Models\Task");
    }

    public function getFileSize() 
    {
        return Storage::size($this->path);
    }
}
