<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;

class AdminAuthenticate
{
    protected $auth;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    public function handle($request, Closure $next)
    {
        if ($this->auth->guard('admin')->guest()) {
            return response()->json([
                'code' => 401,
                'message' => 'Unauthorized'
            ], 401);
        }

        return $next($request);
    }
}







