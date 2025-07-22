<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $roleId)
    {
        $user = Auth::user();
        if (!$user || $user->role_id != $roleId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return $next($request);
    }
} 
