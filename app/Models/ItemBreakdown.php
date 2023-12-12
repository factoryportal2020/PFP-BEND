<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemBreakdown extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'item_id',
        'label_name',
        'value',
    ];


    public function item()
    {
        return $this->belongsTo("App\Models\Item");
    }
}
