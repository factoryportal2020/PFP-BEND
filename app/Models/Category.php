<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'status',

        'admin_id',
        'domain_id',

        'created_by',
        'updated_by',
    ];


    public function categoryImages()
    {
        return $this->hasMany('App\Models\CategoryImage');
    }

    public function items()
    {
        return $this->hasMany('App\Models\Item');
    }

    public function tasks()
    {
        return $this->hasMany('App\Models\Task');
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
        $id = DB::table('categories')->orderBy('id', 'DESC')->value('id');
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
