<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'name',
        'path',

        'created_by',
        'updated_by',
    ];


    public function customer()
    {
        return $this->belongsTo("App\Models\Customer");
    }
}
