<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskBreakdown extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'task_id',
        'label_name',
        'value',
    ];


    public function task()
    {
        return $this->belongsTo("App\Models\Task");
    }
}
