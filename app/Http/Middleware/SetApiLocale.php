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
        if ($request->routeIs('api.v1.contact.store') && array_key_exists('locale', $request->all())) {
            $locale = $request->input('locale');
        }
        if (! in_array($locale, ['ca', 'es'], true)) {
            if ($request->routeIs('api.v1.contact.store')) {
                return response()->json([
                    'success' => false, 'code' => 'validation_failed',
                    'message' => __('contact.validation_failed', [], 'ca'),
                    'errors' => ['locale' => ['Idioma no admès. Utilitza ca o es.']],
                ], 422)->header('Content-Language', 'ca');
            }

            return response()->json(['message' => 'Idioma no admès. Utilitza ca o es.'], 422);
        }

        app()->setLocale($locale);

        return $next($request)->header('Content-Language', $locale);
    }
}
