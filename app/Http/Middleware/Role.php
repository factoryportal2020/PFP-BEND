<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Auth;

class Role
{

    protected $auth;

    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $roles)
    {
        //dd($role);
        if (!Auth::check()) {
            return response()->json(['status' => false,'message'=>"Authenticate Failed"], 200);
        }

        if ($this->auth->guest() || !$request->user()->hasRole($roles)) {
            return response()->json(['status' => false,'message'=>"Role Authenticate Failed"], 200);
        }

        return $next($request);
    }
}
