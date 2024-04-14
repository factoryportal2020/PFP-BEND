<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Enquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'count',
        'comment',
        'code',
        'user_id',
        'item_id',
        'admin_id',
        'phone_no',
    ];

    public function item()
    {
        return $this->belongsTo('App\Models\Item');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
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

    static function getCode()
    {
        $id = DB::table('enquiries')->orderBy('id', 'DESC')->value('id');
        $id = $id + 1;
        $code = "ENQ" . $id;
        return $code;
    }

}
