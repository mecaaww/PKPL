<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class JwtMiddleware
{
    public function handle($request, Closure $next)
    {
        try {
            if ($token = $request->cookie('token')) {
                try {
                    $token = \Illuminate\Support\Facades\Crypt::decrypt($token, false);
                } catch (Exception) {
                    // Token is unencrypted, keep as is
                }
                if (is_string($token) && str_contains($token, '|')) {
                    $token = explode('|', $token, 2)[1];
                }
                $request->headers->set('Authorization', 'Bearer '.$token);
                $user = JWTAuth::setToken($token)->authenticate();
            } else {
                $user = JWTAuth::parseToken()->authenticate();
            }

            if ($user->isAdmin() && ! $request->is('admin*') && ! $request->is('logout')) {
                return redirect()->route('admin.dashboard');
            }

        } catch (Exception $e) {
            if ($e instanceof \PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException) {
                return redirect()->route('login')->with('error', 'Token tidak valid');
            } elseif ($e instanceof \PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException) {
                return redirect()->route('login')->with('error', 'Sesi berakhir, silakan login lagi');
            } else {
                return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu');
            }
        }

        return $next($request);
    }
}
