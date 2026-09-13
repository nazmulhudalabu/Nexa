<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiAuthenticate
{
    public function handle(Request $request, Closure $next): mixed
    {
        $token = $request->bearerToken();
        $user = $token ? User::where('api_token_hash', hash('sha256', $token))->where('api_token_expires_at', '>', now())->first() : null;
        abort_unless((bool) $user, 401, 'Unauthenticated.');
        Auth::setUser($user);
        return $next($request);
    }
}