<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserToken;
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
            $user->level = 2;
            $user->nama = $request->nama;
            $user->secret_question = $request->secretQuestion;
            $user->secret_answer = $request->secretAnswer;
            $user->password = Hash::make($request->password);
            $user->save();

            return response()->json(["success" => true, "messageHandler" => "User created successfully.", "data" => $user]);
        } catch (QueryException $e) {
            report($e);

            if ($e->getCode() == 23000)
                return response()->json(["messageHandler" => "Username telah dipakai"], 400);
        }
    }

    public function login(Request $request)
    {
        $user = User::where('username', $request->username)->first();

        if (($user) == null) {
            return response()->json(["messageHandler" => "Username tidak terdaftar"], 400);
        }

        $check = Hash::check($request->password, $user->password);

        if (!$check) {
            return response()->json(["messageHandler" => "Password salah"], 400);
        }
        if ($user->level == '2') {
            $token = $this->generate_token($user->id);
            if ($token['status']) {
                $user->token = $token['token'];
                return response()->json(["user" => $user]);
            }
        }
        return response()->json(["messageHandler" => "Anda bukan User"], 400);

    }

    public function login_admin(Request $request)
    {
        $user = User::where('username', $request->username)->first();

        if (($user) == null) {
            return response()->json(["messageHandler" => "Username tidak terdaftar"], 400);
        }

        $check = Hash::check($request->password, $user->password);

        if (!$check) {
            return response()->json(["messageHandler" => "Password salah"], 400);
        }
        if ($user->level == '1') {
            $token = $this->generate_token($user->id);
            if ($token['status']) {
                $user->token = $token['token'];
                return response()->json(["user" => $user]);
            }
            return response()->json(["user" => $user]);
        }
        return response()->json(["messageHandler" => "Anda bukan Admin"], 400);

    }

    public function check_token(Request $request)
    {
        $headerToken = $request->header("token");
        $token = UserToken::where('token', $headerToken)->first();
        return ["headerToken" => $headerToken, "token" => $token];
    }

    private function generate_token($user_id)
    {
        $token = new UserToken();
        $token->token = $this->generateRandomString();
        $token->user_id = $user_id;
        $token->device_token = '';
        $token->expired_date = date('Y-m-d H:i:s', strtotime('1 month'));
        $token->save();
        return ["status" => true, "token" => $token->token];
    }
    public function save_device_token(Request $request)
    {
        $token = UserToken::where('token', $request->token)->first();
        $token->device_token = $request->device_token;
        $token->update();
        return ["status" => true, "token" => $token->token];
    }
    private function generateRandomString($length = 64)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function show($id)
    {
        $user = User::find($id);

        return response()->json(["data" => $user]);
    }
    public function show_all_users()
    {
        $user = User::where('level', '2')->get();

        return response()->json(["data" => $user]);
    }
    public function showUsername($username)
    {
        $user = User::where('username', $username)->first();

        return response()->json(["data" => $user]);
    }

    public function checkAnswer($id, Request $request)
    {
        $user = User::find($id);

        if ($user->secret_answer != $request->secretAnswer) {
            return response()->json(["messageHandler" => "Jawaban salah"], 400);
        }
        return response()->json(["messageHandler" => "Jawaban benar"]);
    }

    public function reset($id, Request $request)
    {
        $user = User::find($id);

        $user->password = Hash::make($request->password);
        $user->update();

        return response()->json(["messageHandler" => "Password user dengan id " . $id . " berhasil direset"]);
    }
    public function destroy($id)
    {
        $user = User::find($id);

        $user->delete();

        return response()->json(["success" => true, "messageHandler" => "User deleted successfully.", "data" => $user]);
    }
    public function update(Request $request, User $user)
    {
        $input = $request->all();
        $user->username = $input['username'];
        $user->nama = $input['nama'];
        $user->password = Hash::make($request->password);


        $user->save();
        return response()->json(["success" => true, "messageHandler" => "User updated successfully.", "data" => $user]);

    }
}