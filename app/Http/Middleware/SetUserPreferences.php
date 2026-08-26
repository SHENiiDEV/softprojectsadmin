<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetUserPreferences
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();

            // Apply language preference
            if ($user->language) {
                App::setLocale($user->language);
            }

            // Apply timezone preference for current request
            if ($user->timezone) {
                config(['app.timezone' => $user->timezone]);
                date_default_timezone_set($user->timezone);
            }
        }

        return $next($request);
    }
}
