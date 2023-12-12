<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkerImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'worker_id',
        'name',
        'path',

        'created_by',
        'updated_by',
    ];


    public function worker()
    {
        return $this->belongsTo("App\Models\Worker");
    }
}
