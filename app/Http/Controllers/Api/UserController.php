<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;

use function PHPUnit\Framework\isNull;

class UserController extends Controller
{
    //
    public function register(Request $request)
    {
        try {
                $user = new User();
                $user->username = $request->username;
                $user->nama = $request->nama;
                $user->secret_question = $request->secretQuestion;
                $user->secret_answer = $request->secretAnswer;
                $user->password = Hash::make($request->password);
                $user->save();
                
                return response()->json(["success" => true, "message" => "User created successfully.", "data" => $user]);
            } catch (QueryException $e) {
                report($e);
         
                if($e->getCode() == 23000)
                return response()->json(["message" => "Username telah dipakai"], 400);
            }
    }

    public function login(Request $request)
    {
        $user = User::where('username', $request->username)->first();

        if(($user) == null){
            return response()->json(["message" => "Username tidak terdaftar"], 400);
        }

        $check = Hash::check($request->password, $user->password);

        if($check)
            return response()->json(["user" => $user]);
        
        return response()->json(["message" => "Password salah"], 400);
    }

    public function show($id) {
        $user = User::find($id);

        return response()->json(["data" => $user]);
    }
    public function showUsername($username) {
        $user = User::where('username',$username)->first();

        return response()->json(["data" => $user]);
    }

    public function checkAnswer($id, Request $request) {
        $user = User::find($id);

        if($user->secret_answer != $request->secretAnswer){
            return response()->json(["message" => "Jawaban salah"], 400);
        }
        return response()->json(["message" => "Jawaban benar"]);
    }

    public function reset($id, Request $request) {
        $user = User::find($id);

        $user->password = Hash::make($request->password);
        $user->update();

        return response()->json(["message" => "Password user dengan id ".$id." berhasil direset"]);
    }
}
