<?php

namespace App\Http\Middleware;
use Illuminate\Support\Facades\Config;

use Closure;

class EnforceTenancy
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
        Config::set('database.default', 'tenant');
        return $next($request);
    }
}
