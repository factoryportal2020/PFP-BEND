<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Role extends Model
{
    use HasFactory;
    //1 = superadmin, 
    //2 = admin, 
    //3 = customer, 
    //4 = worker, 

    static function superadmin()
    {
        $id = DB::table('roles')->where('name', "superadmin")->value('id');
        return ($id) ? $id : 1;
    }

    static function admin()
    {
        $id = DB::table('roles')->where('name', "admin")->value('id');
        return ($id) ? $id : 2;
    }
    
    static function customer()
    {
        $id = DB::table('roles')->where('name', "customer")->value('id');
        return ($id) ? $id : 3;
    }

    static function worker()
    {
        $id = DB::table('roles')->where('name', "worker")->value('id');
        return ($id) ? $id : 4;
    }
}
