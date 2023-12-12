<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'code',
        'category_id',
        'customer_id',
        'worker_id',
        'status', // 'Unassigned','Assigned','Inprogress','Holding','Restarted','Cancelled','Pending','Completed','Delivered'

        'specification',
        'quantity',
        'description',
        'price',
        
        'start_date',
        'end_date',

        'admin_id',
        'domain_id',

        'created_by',
        'updated_by',
    ];

    public function setStartDateAttribute($value)
    {
        $start_date = ($value==""||$value==null||!$value)?Null:date("Y-m-d H:i:s", strtotime($value));
        $this->attributes['start_date'] = $start_date;
    }

    public function setEndDateAttribute($value)
    {
        $end_date = ($value==""||$value==null||!$value)?Null:date("Y-m-d H:i:s", strtotime($value));
        $this->attributes['end_date'] = $end_date;
    }

    public function taskImages()
    {
        return $this->hasMany('App\Models\TaskImage');
    }

    public function mainImages() {
        return $this->taskImages()->where('type','=', "main")->where('extension','=', "image");
    }

    public function initialImages() {
        return $this->taskImages()->where('type','=', "initial");
    }

    public function workingImages() {
        return $this->taskImages()->where('type','=', "working");
    }

    public function completedImages() {
        return $this->taskImages()->where('type','=', "completed");
    }

    public function deliveredImages() {
        return $this->taskImages()->where('type','=', "delivered");
    }

    public function taskSpecifications()
    {
        return $this->hasMany('App\Models\TaskSpecification');
    }

    public function taskBreakdowns()
    {
        return $this->hasMany('App\Models\TaskBreakdown');
    }

    public function taskHistories()
    {
        return $this->hasMany('App\Models\TaskHistory');
    }

    public function category()
    {
        return $this->belongsTo('App\Models\Category');
    }

    public function customer()
    {
        return $this->belongsTo('App\Models\Customer');
    }

    public function worker()
    {
        return $this->belongsTo('App\Models\Worker');
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
        $id = DB::table('tasks')->orderBy('id', 'DESC')->value('id');
        $id = $id + 1;
        $code = "TASK" . $id;
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

    // public function getStartDateAttribute($value)
    // {
    //     return date("d-m-Y H:i:s", strtotime($value));
    // }

    // public function getEndDateAttribute($value)
    // {
    //     return date("d-m-Y H:i:s", strtotime($value));
    // }

    public function getEncrptIDAttribute($value)
    {
        return $encrypted = encryptID($this->id, 'e');
    }
}
