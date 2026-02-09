<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HandleLarascordErrors
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Check if response contains Larascord error
        if ($response->getStatusCode() === 500 || $response->getStatusCode() === 403) {
            $content = $response->getContent();
            
            if (str_contains($content, 'larascord_message') || str_contains($content, 'guilds')) {
                return response()->view('errors.guild-access-denied', [], 403);
            }
        }
        
        return $response;
    }
}
