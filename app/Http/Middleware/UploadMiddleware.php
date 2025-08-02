<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UploadMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Set PHP configuration for large file uploads
        ini_set('upload_max_filesize', '50M');
        ini_set('post_max_size', '50M');
        ini_set('memory_limit', '256M');
        ini_set('max_execution_time', 300);
        ini_set('max_input_time', 300);
        
        return $next($request);
    }
} 
