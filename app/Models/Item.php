<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Item extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'price',
        'category_id',
        'status',

        'specification',
        'note',
        'status',

        'admin_id',
        'domain_id',

        'created_by',
        'updated_by',
    ];


    public function itemImages()
    {
        return $this->hasMany('App\Models\ItemImage');
    }

    public function mainImages() {
        return $this->itemImages()->where('type','=', "main");
    }

    public function otherImages() {
        return $this->itemImages()->where('type','=', "other");
    }

    public function itemSpecifications()
    {
        return $this->hasMany('App\Models\ItemSpecification');
    }

    public function itemBreakdowns()
    {
        return $this->hasMany('App\Models\ItemBreakdown');
    }

    public function category()
    {
        return $this->belongsTo('App\Models\Category');
    }

    public function domain()
    {
        return $this->belongsTo('App\Models\Domain');
    }

    public function admin()
    {
        return $this->belongsTo('App\Models\Admin');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    static function getCode()
    {
        $id = DB::table('items')->orderBy('id', 'DESC')->value('id');
        $id = $id + 1;
        $code = "CAT" . $id;
        return $code;
    }

    public function getCreatedAtAttribute($value)
    {
        return date("d-m-Y H:i", strtotime($value));
    }

    public function getUpdatedAtAttribute($value)
    {
        return date("d-m-Y H:i", strtotime($value));
    }

    public function getEncrptIDAttribute($value)
    {
        return $encrypted = encryptID($this->id, 'e');
    }
}
