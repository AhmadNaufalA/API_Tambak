<?php

namespace App\Http\Middleware;

use App\Models\UserToken;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;

class AuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $headerToken = $request->header("token");
        $token = UserToken::where('token', $headerToken)->first();

        if (is_null($token) || is_null($token->expired_date))
            return response()->json(["messageHandler" => "Unauthorized, please re-login"], 401);

        $today = Carbon::now(new \DateTimeZone('Asia/Jakarta'));
        $last = Carbon::parse($token->expired_date); //if there are no records it will fail

        if ($today->lte($last))
            return $next($request);

        return response()->json(["messageHandler" => "Unauthorized, please re-login"], 401);

    }
}