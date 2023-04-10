<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use App\Models\UserToken;


use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    protected function validate_token(Request $request)
    {
        $token = $request->header('Token');
        $user_token = UserToken::where('token', $token)->first();

        if (empty($user_token)) {
            // return response()->json(["message" => "Invalid token"], 400);
            return false;
        }
        if (strtotime($user_token->expired_date) < strtotime(date('Y-m-d H:i:s'))) {
            // return response()->json(["message" => "Invalid token"], 400);
            return false;
        }
        return true;
    }
}