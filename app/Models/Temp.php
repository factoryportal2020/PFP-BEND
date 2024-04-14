<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Temp extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id',
        'admin_id',
        'company_name',
        'site_url',
        'email',
        'phone_no',

        'landline_no',
        'whatsapp_no',
        'address',
        'instagram_link',

        'facebook_link',
        'twitter_link',

        'code',
        'status',

        'launch_at',
        'created_by',
        'updated_by',
    ];


    public function websiteImages()
    {
        return $this->hasMany('App\Models\TempImage','website_id');
    }

    public function logoImages()
    {
        return $this->websiteImages()->where('type', '=', "logo")->where('delete_at', '=', null);
    }

    public function aboutImages()
    {
        return $this->websiteImages()->where('type', '=', "about")->where('delete_at', '=', null);
    }

    public function featureImages()
    {
        return $this->websiteImages()->whereIn('type', ["feature1", "feature2", "feature3"])->orderBy("type", "ASC");
    }

    public function bannerImages()
    {
        return $this->websiteImages()->whereIn('type', ["banner1", "banner2", "banner3"])->where('delete_at', '=', null)->orderBy("type", "ASC");
    }

    public function domain()
    {
        return $this->belongsTo('App\Models\Domain');
    }

    public function admin()
    {
        return $this->belongsTo('App\Models\Admin');
    }

    static function getCode()
    {
        $id = DB::table('temps')->orderBy('id', 'DESC')->value('id');
        // $id = ($id) ? $id : 0;
        $id = $id + 1;
        $code = "WEB" . $id;
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
        return encryptID($this->id, 'e');
    }
}
