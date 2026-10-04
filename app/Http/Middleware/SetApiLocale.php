<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetApiLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $query = $request->query();
        $locale = array_key_exists('locale', $query) ? $query['locale'] : 'ca';
        if (! in_array($locale, ['ca', 'es'], true)) {
            return response()->json(['message' => 'Idioma no admès. Utilitza ca o es.'], 422);
        }

        app()->setLocale($locale);

        return $next($request)->header('Content-Language', $locale);
    }
}
