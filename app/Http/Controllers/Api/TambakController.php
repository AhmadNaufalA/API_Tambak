<?php

namespace App\Http\Controllers\Api;

use App\Models\Tambak;
use App\Models\KualitasAir;

use App\Http\Controllers\Controller;
use App\Models\Log;
use App\Models\TambakPreference;
use Google\Cloud\Storage\Connection\Rest;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class TambakController extends Controller
{

    private function failed()
    {
        return response()->json(["messageHandler" => "Invalid token"], 400);
    }
    public function idList($user_id, Request $request)
    {
        // if (!$this->validate_token($request)) {
        //     return response()->json(["message" => "Invalid token"], 400);
        // }
        $tambak = Tambak::select('id')->where('id_user', $user_id)->get();

        return response()->json([
            "success" => true,
            "messageHandler" => "KualitasAir List By User",
            "data" => ($tambak)
        ]);
    }
    public function userOwned($id, Request $request)
    {
        // if (!$this->validate_token($request)) {
        //     return response()->json(["message" => "Invalid token"], 400);
        // }
        $tambak = Tambak::where('id_user', $id)->orderBy('id', 'desc')->get();

        foreach ($tambak as $key => $value) {
            $badCheck = false;
            $kualitasAir = KualitasAir::where('id_tambak', $value->id)->orderBy('waktu', 'desc')->first();

            if ($kualitasAir != null) {
                $checkers = [
                    'pH' => [7, 9],
                    'TDS' => [15, 25],
                    'Suhu' => [26, 30],
                    //'Ketinggian' => [25, 40],
                    'Oksigen' => [4, 8],
                    'Kekeruhan' => [25, 40],
                ];
                foreach ($checkers as $checkerKey => $value) {

                    if ($tambak[$key]->{$checkerKey} == 1 && $kualitasAir->{$checkerKey} < $value[0] || $kualitasAir->{$checkerKey} > $value[1]) {
                        $badCheck = true;
                        break;
                    }
                }
            } else {
                $badCheck = true;
            }

            $tambak[$key]->status = $badCheck;
        }

        return response()->json([
            "success" => true,
            "messageHandler" => "KualitasAir List By User",
            "data" => ($tambak)
        ]);
    }
    public function index(Request $request)
    {
        // if (!$this->validate_token($request)) {
        //     return response()->json(["message" => "Invalid token"], 400);
        // }
        $tambak = Tambak::orderBy('id', 'desc')->get();

        return response()->json([
            "success" => true,
            "messageHandler" => "KualitasAir List",
            "data" => ($tambak)
        ]);
    }
    public function store(Request $request)
    {
        // if (!$this->validate_token($request)) {
        //     return response()->json(["message" => "Invalid token"], 400);
        // }
        try {

            $input = $request->all();
            $tambak = Tambak::create($input);

            return response()->json(["success" => true, "messageHandler" => "Tambak created successfully.", "data" => $tambak]);
        } catch (QueryException $e) {
            if ($e->getCode() == 23000) {
                return response()->json(["messageHandler" => "User ID tidak ditemukan, mohon login ulang"], 401);
            }
        }
    }

    public function show($id, Request $request)
    {
        // if (!$this->validate_token($request)) {
        //     return response()->json(["message" => "Invalid token"], 400);
        // }
        $product = Tambak::find($id);
        if (is_null($product)) {
            return $this->sendError('Tambak not found.');
        }

        // $kualitasAir = $product->kualitasAir->;

        $date = date($request->query('date'));

        if ($request->query('date') == null) {
            $kualitasAir = KualitasAir::orderBy('waktu', 'desc')->where('id_tambak', $id)->first();
        } else {

            $kualitasAir = KualitasAir::orderBy('waktu', 'desc')->whereDate('waktu', '=', $date)->where('id_tambak', $id)->first();
        }


        return response()->json(["success" => true, "messageHandler" => "KualitasAir retrieved successfully.", "data" => $product, 'kualitasAir' => $kualitasAir]);
    }

    public function update(Request $request, Tambak $tambak)
    {
        // if (!$this->validate_token($request)) {
        //     return response()->json(["message" => "Invalid token"], 400);
        // }
        $input = $request->all();
        $tambak->name = $input['name'];
        $tambak->desc = $input['desc'];
        $tambak->pH = $input['pH'];
        $tambak->Suhu = $input['Suhu'];
        $tambak->TDS = $input['TDS'];
        //$tambak->Ketinggian = $input['Ketinggian'];
        $tambak->Oksigen = $input['Oksigen'];
        $tambak->Kekeruhan = $input['Kekeruhan'];
        $tambak->save();
        return response()->json(["success" => true, "messageHandler" => "Tambak updated successfully.", "data" => $tambak]);

        // $validator = Validator::make($input, ['name' => 'required', 'detail' => 'required']);
        // if ($validator->fails()) {
        //     return $this->sendError('Validation Error.', $validator->errors());
        // }
        // $product->name = $input['name'];
        // $product->detail = $input['detail'];
        // $product->save();
        // return response()->json(["success" => true, "message" => "Tambak updated successfully.", "data" => $tambak]);
    }

    // public function destroy(KualitasAir $product)
    // {
    //     $product->delete();
    //     return response()->json(["success" => true, "message" => "KualitasAir deleted successfully.", "data" => $product]);
    // }

    public function destroy($id, Request $request)
    {
        // if (!$this->validate_token($request)) {
        //     return response()->json(["message" => "Invalid token"], 400);
        // }
        $tambak = Tambak::find($id);

        $tambak->delete();

        return response()->json(["success" => true, "messageHandler" => "Tambak deleted successfully.", "data" => $tambak]);
    }

    public function between($id, Request $request)
    {
        // if (!$this->validate_token($request)) {
        //     return response()->json(["message" => "Invalid token"], 400);
        // }
        // $air = KualitasAir::all(['id', 'waktu', 'pH']);
        $from = date($request->query('from'));
        $to = date($request->query('to'));
        $column = $request->query('column');

        $air = KualitasAir::where('id_tambak', $id)->whereBetween('waktu', [$from, $to])->orderBy('waktu', 'ASC')->get(['id', 'waktu', $column]);

        return response()->json([
            "success" => true,
            "messageHandler" => "KualitasAir List",
            "data" => ($air)
        ]);
    }

    public function logs($id, Request $request)
    {
        // if (!$this->validate_token($request)) {
        //     return response()->json(["message" => "Invalid token"], 400);
        // }
        $logs = Log::where('id_tambak', $id)->orderBy('waktu', 'DESC')->get();

        return response()->json([
            "success" => true,
            "messageHandler" => "Log list",
            "data" => ($logs)
        ]);
    }
}