<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'name',
        'path',
        'type',

        'created_by',
        'updated_by',
    ];


    public function item()
    {
        return $this->belongsTo("App\Models\Item");
    }
}
