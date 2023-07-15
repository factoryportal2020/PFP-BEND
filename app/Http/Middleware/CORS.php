<?php
# File: app\Http\Middleware\CORS.php
# Create file with below code in above location. And at the end of the file there are other instructions also. 
# Please check. 

namespace App\Http\Middleware;

use Closure;

class CORS
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // $origin = env('REACT_REQUEST_API_URL');
        $origin = "*";
        //dd($origin);
        return $next($request)
            ->header('Access-Control-Allow-Origin', $origin)
            ->header('Access-Control-Allow-Headers', $origin)
            ->header('Access-Control-Allow-Methods', 'GET,HEAD,OPTIONS,POST,PUT,DELETE');
    }
}
