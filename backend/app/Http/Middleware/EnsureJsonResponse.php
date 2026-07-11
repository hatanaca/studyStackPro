<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('PATCH')) {
            $contentTypeFormat = $request->getContentTypeFormat();

            // Only allow 'json' format for mutation requests; reject form, xml, multipart, text, etc.
            if ($contentTypeFormat !== 'json') {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'UNSUPPORTED_MEDIA_TYPE',
                        'message' => 'Apenas application/json é aceito.',
                    ],
                ], 415);
            }
        }

        return $next($request);
    }
}
