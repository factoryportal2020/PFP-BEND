<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskSpecification extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'task_id',
        'label_name',
        'type',
        'value',
    ];


    public function task()
    {
        return $this->belongsTo("App\Models\Task");
    }
}
