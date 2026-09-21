<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class NoCache
{
    public function handle(Request $request, Closure $next)
    {
        /*
         * Let the request continue through the application first.
         *
         * The resulting response may be a normal Laravel response
         * or a StreamedResponse, such as a private file response.
         */
        $response = $next($request);

        /*
         * Access the Symfony response headers directly.
         *
         * This works with both normal responses and streamed responses.
         */
        $response->headers->set(
            'Cache-Control',
            'no-cache, no-store, max-age=0, must-revalidate'
        );

        $response->headers->set(
            'Pragma',
            'no-cache'
        );

        $response->headers->set(
            'Expires',
            'Sat, 01 Jan 2000 00:00:00 GMT'
        );

        /*
         * Return the response back through Laravel's HTTP pipeline.
         */
        return $response;
    }
}