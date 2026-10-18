<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale', config('app.locale', 'fr'));

        if (! in_array($locale, ['fr', 'en'])) {
            $locale = 'fr';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
