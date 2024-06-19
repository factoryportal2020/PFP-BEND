<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use App\Models\Permission;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Session;
use App\Models\Admin;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'phone_no',
        'role_id',
        'domain_id',
        'password',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    public function role()
    {
        return $this->belongsTo('App\Models\Role');
    }

    // public function admin()
    // {
    //     return $this->hasOne('App\Models\Admin');
    // }

    public function domain()
    {
        return $this->belongsTo('App\Models\Role');
    }


    public function getAdminIDAttribute($value)
    {
        return DB::table('admins')->where('user_id', $this->id)->value('id');
    }

    public function getEncrptIDAttribute($value)
    {
        return encryptID($this->id, 'e');
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }


    public function existUser($field, $value)
    {
        $user = $this->where($field, '=', $value)->first();
        return $user;
    }

    public function hasRole($roles)
    {
        $roles = explode('|', $roles);
        foreach ($roles as $role) {
            if (($this->role->name == $role)) {
                return true;
            }
        }
        return false;
    }

    public function permissions()
    {
        $role_id = $this->role_id;
        $result = [];
        $permission_role = DB::table('permission_role')->where('role_id', $role_id)->pluck('permission_id');
        if (!empty($permission_role)) {
            $result = Permission::whereIn('id', $permission_role)->pluck('name');
        }
        return $result;
    }

    /**
     * Send a password reset notification to the user.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        if (session('AdminName')) {
            $company_name  = session('AdminName');
        } else {
            $company_name  =   "";
        }

        $url = env('REACT_REQUEST_API_URL') . $company_name . '/reset/password/' . $token;

        $this->notify(new ResetPasswordNotification($url));
    }
}
