<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'task_id',
        'status',
        'comment',

        'created_by',
        'updated_by',
    ];


    public function task()
    {
        return $this->belongsTo("App\Models\Task");
    }
}
