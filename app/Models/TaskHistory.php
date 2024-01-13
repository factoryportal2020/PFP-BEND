<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskHistory extends Model
{
    use HasFactory;

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

    public function user()
    {
        return $this->belongsTo("App\Models\User", 'updated_by', 'id');
    }

    public function getCreatedAtAttribute($value)
    {
        return date("d-m-Y H:i", strtotime($value));
    }

    public function getUpdatedAtAttribute($value)
    {
        return date("d-m-Y H:i", strtotime($value));
    }
}
