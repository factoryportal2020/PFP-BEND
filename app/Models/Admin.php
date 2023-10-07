<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id',
        'user_id',
        'name',
        'description',
    ];

    public function domain()
    {
        return $this->belongsTo('App\Models\Domain');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
}
