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

    public function tasks()
    {
        return $this->hasMany('App\Models\Task');
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



    public function deliveredTasks()
    {
        $tasks = $this->tasks()->where("tasks.status", "Delivered");
        return $tasks; 
    }
    
    public function completedTasks()
    {
        $tasks = $this->tasks()->where("tasks.status", "Completed");
        return $tasks; 
    }

    public function pendingTasks()
    {
        $tasks = $this->tasks()->where("tasks.status", "Pending");
        return $tasks; 
    }

    public function cancelledTasks()
    {
        $tasks = $this->tasks()->where("tasks.status", "Cancelled");
        return $tasks; 
    }

    public function restartedTasks()
    {
        $tasks = $this->tasks()->where("tasks.status", "Restarted");
        return $tasks; 
    }
    
    public function holdingTasks()
    {
        $tasks = $this->tasks()->where("tasks.status", "Holding");
        return $tasks; 
    }

    public function inprogressTasks()
    {
        $tasks = $this->tasks()->where("tasks.status","Inprogress");
        return $tasks; 
    }

    public function assignedTasks()
    {
        $tasks = $this->tasks()->where("tasks.status", "Assigned");
        return $tasks; 
    }

    public function unAssignedTasks()
    {
        $tasks = $this->tasks()->where("tasks.status", "Unassigned");
        return $tasks; 
    }

    // 'Unassigned','Assigned','Inprogress','Holding','Restarted','Cancelled','Pending','Completed','Delivered'

    public function getAllTasksCountAttribute()
    {
        $tasks['unassigned_count'] = $this->unAssignedTasks()->count();
        $tasks['assigned_count'] = $this->assignedTasks()->count();
        $tasks['inprogress_count'] = $this->inprogressTasks()->count();
        $tasks['holding_count'] = $this->holdingTasks()->count();
        $tasks['restarted_count'] = $this->restartedTasks()->count();
        $tasks['cancelled_count'] = $this->cancelledTasks()->count();
        $tasks['pending_count'] = $this->pendingTasks()->count();
        $tasks['completed_count'] = $this->completedTasks()->count();
        $tasks['delivered_count'] = $this->deliveredTasks()->count();
        return $tasks; 
    }
}
