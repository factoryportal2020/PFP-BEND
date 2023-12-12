<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Worker extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'code',
        'email',
        'phone_no',
        'whatsapp_no',
        'instagram_id',
        'gender',
        'address',
        'state',
        'specialist',
        'city',
        'notes',
        'status',

        'admin_id',
        'domain_id',
        'user_id',

        'created_by',
        'updated_by',
    ];


    public function workerImages()
    {
        return $this->hasMany('App\Models\WorkerImage');
    }

    public function domain()
    {
        return $this->belongsTo('App\Models\Role');
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
        $id = DB::table('workers')->orderBy('id', 'DESC')->value('id');
        $id = $id + 1;
        $code = "WRK" . $id;
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
