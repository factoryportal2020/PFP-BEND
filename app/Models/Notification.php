<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'domain_id',
        'sender_id',
        'receiver_id',
        'menu',
        'menu_id',
        'message',
        'is_viewed',
        'link',
    ];

    public function domain()
    {
        return $this->belongsTo('App\Models\Role');
    }

    public function admin()
    {
        return $this->belongsTo('App\Models\Admin');
    }


    static function getCode()
    {
        $id = DB::table('notifications')->orderBy('id', 'DESC')->value('id');
        $id = $id + 1;
        $code = "NOTY" . $id;
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
