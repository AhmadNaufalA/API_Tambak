<?php

namespace App\Http\Controllers\Api;

use App\Models\KualitasAir;

use App\Http\Controllers\Controller;
use App\Models\Log;
use App\Models\LogRusak;
use App\Models\Tambak;
use App\Models\User;
use App\Models\UserToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Kreait\Firebase\Exception\Messaging\QuotaExceeded;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use PhpMqtt\Client\Facades\MQTT;

class KualitasAirController extends Controller
{
    // public function index()
    // {
    //     $mqtt = MQTT::connection();
    //     $mqtt->publish('some/topic', 'foo', 1);
    //     $mqtt->publish('some/other/topic', 'bar', 2, true); // Retain the message
    //     $mqtt->loop(true);

    //     return response()->json([
    //         "success" => true,
    //         "message" => "KualitasAir List",

    //     ]);
    // }
    public function store(Request $request)
    {
        // if (!$this->validate_token($request)) {
        //     return response()->json(["message" => "Invalid token"], 400);
        // }
        $input = $request->all();

        $previousKualitasAir = KualitasAir::where("id_tambak", $input['id_tambak'])->orderBy('waktu', 'DESC')->first();
        $kualitasAir = KualitasAir::create($input);
        

        if ($previousKualitasAir == null)
            return response()->json(["success" => true, "messageHandler" => "KualitasAir created successfully.", "data" => $kualitasAir]);

        $messaging = app('firebase.messaging');

        $tambak = Tambak::find($request->id_tambak);
        $checkers = [
            'pH' => [6.5, 9.5],
            'TDS' => [1000, 3000],
            'Suhu' => [28, 33],
            //'Ketinggian' => [25, 40],
            'Oksigen' => [4, 8.5],
            'Kekeruhan' => [0, 30],
        ];

        $margin = [
            'pH' => 4,
            'TDS' => 2000,
            'Suhu' => 6,
            //'Ketinggian' => 20,
            'Oksigen' => 5,
            'Kekeruhan' => 31,
        ];

        $badCheck = [];
        foreach ($checkers as $key => $value) {
            if ($tambak[$key] == 1 && $request->all()[$key] < $value[0] || $request->all()[$key] > $value[1]) {
                array_push($badCheck, $key);
            }
        }

        $badMargin = [];
        foreach ($margin as $key => $value) {
            if ($tambak[$key] == 1 && abs($request->all()[$key] - $previousKualitasAir[$key]) >= $value) {
                array_push($badMargin, $key);
            }
        }


        if (count($badCheck) > 0) {

            $badVariablesString = implode(", ", $badCheck);

            $badVariableValues = implode(
                ", ",
                array_map(
                    function ($k) use ($request) {
                        return $request->all()[$k];
                    },
                    $badCheck
                )
            );

            Log::insert(
                array_map(
                    function ($bad) use ($tambak, $checkers) {
                        $isi = "";

                        switch ($bad) {
                            case 'pH':
                                $isi = "Pada Tambak " . $tambak->name . ", jika " . $bad . " kurang dari " . $checkers[$bad][0] . " maka perlu diberikan..., apabila lebih dari " . $checkers[$bad][1] . " perlu diberi ...";
                                break;
                            case 'TDS':
                                $isi = "Pada Tambak " . $tambak->name . ", jika " . $bad . " kurang dari " . $checkers[$bad][0] . " maka perlu diberikan..., apabila lebih dari " . $checkers[$bad][1] . " perlu diberi ...";
                                break;
                            case 'Suhu':
                                $isi = "Pada Tambak " . $tambak->name . ", jika " . $bad . " kurang dari " . $checkers[$bad][0] . " maka perlu diberikan..., apabila lebih dari " . $checkers[$bad][1] . " perlu diberi ...";
                                break;
                            case 'Oksigen':
                                $isi = "Pada Tambak " . $tambak->name . ", jika " . $bad . " kurang dari " . $checkers[$bad][0] . " maka perlu diberikan..., apabila lebih dari " . $checkers[$bad][1] . " perlu diberi ...";
                                break;
                            // case 'Ketinggian':
                            //   $isi = "Pada Tambak " . $tambak->name . ", jika " . $bad . " kurang dari " . $checkers[$bad][0] . " maka perlu diberikan..., apabila lebih dari " . $checkers[$bad][1] . " perlu diberi ...";
                            // break;
                            case 'Kekeruhan':
                                $isi = "Pada Tambak " . $tambak->name . ", jika " . $bad . " kurang dari " . $checkers[$bad][0] . " maka perlu diberikan..., apabila lebih dari " . $checkers[$bad][1] . " perlu diberi ...";
                                break;
                            default:
                                break;
                        }

                        return [
                            "id_tambak" => $tambak->id,
                            "isi" => $isi
                        ];
                    },
                    $badCheck
                )
            );

            $title = $badVariablesString . " tidak optimal";
            $body = $badVariablesString . " tidak normal di " . $tambak->name . "  yaitu " . $badVariableValues;

            $deviceToken = UserToken::where('token', $request->header('Token'));
            // tambahin deviceToken ke cloudMessage biar kekirim ke specific device
            // uncomment validate_header
            // kalo mau pake aid tambah, get user idnya, then token
            $message = CloudMessage::withTarget(
                'topic', $tambak->id,
            )
                ->withData(
                    [
                        'title' => $title,
                        'body' => $body,
                    ]
                ); // optional
            //$message = CloudMessage::withTarget('token', $deviceToken)
            // ->withNotification(['title' => 'My title', 'body' => 'My Body'])
            // ;
            ;

            try {
                $messaging->send($message);
            } catch (QuotaExceeded $e) {
            }
        }
        if (count($badMargin) > 0) {

            $badVariablesString = implode(", ", $badMargin);

            $title = "Sensor " . $badVariablesString . " kemungkinan rusak";
            $body = "Pada " . $tambak->name;

            $message = CloudMessage::withTarget('topic', $tambak->id)
                ->withData(
                    [
                        'title' => $title,
                        'body' => $body,
                    ]
                ); // optional
            ;

            LogRusak::insert(
                array_map(
                    function ($bad) use ($tambak, $checkers) {
                        $isi = "";

                        switch ($bad) {
                            case 'pH':
                                $isi = "Sensor " . $bad . "pada tambak " . $tambak->name . " kemungkinan rusak karena fluktuatif";
                                break;
                            case 'TDS':
                                $isi = "Sensor " . $bad . "pada tambak " . $tambak->name . " kemungkinan rusak karena fluktuatif";
                                break;
                            case 'Suhu':
                                $isi = "Sensor " . $bad . "pada tambak " . $tambak->name . " kemungkinan rusak karena fluktuatif";
                                break;
                            case 'Oksigen':
                                $isi = "Sensor " . $bad . "pada tambak " . $tambak->name . " kemungkinan rusak karena fluktuatif";
                                break;
                            // case 'Ketinggian':
                            //     $isi = "Jika " . $bad . " kurang dari " . $checkers[$bad][0] . " maka perlu diberikan..., apabila lebih dari " . $checkers[$bad][1] . " perlu diberi ...";
                            //     break;
                            case 'Kekeruhan':
                                $isi = "Sensor " . $bad . "pada tambak " . $tambak->name . " kemungkinan rusak karena fluktuatif";
                                break;
                            default:
                                break;
                        }

                        return [
                            "id_tambak" => $tambak->id,
                            "isi" => $isi
                        ];
                    },
                    $badCheck
                )
            );
            try {
                $messaging->send($message);
            } catch (QuotaExceeded $e) {
            }
        }
        return response()->json(["success" => true, "messageHandler" => "KualitasAir created successfully.", "data" => $kualitasAir]);
    }

// public function show($id)
// {
//     $product = KualitasAir::find($id);
//     if (is_null($product)) {
//         return $this->sendError('KualitasAir not found.');
//     }
//     return response()->json(["success" => true, "message" => "KualitasAir retrieved successfully.", "data" => $product]);
// }
// public function single(Request $request){
//     $date = date($request->query('date'));

//     if($request->query('date') == null){
//         $air = KualitasAir::orderBy('waktu', 'desc')->first();
//     }else{

//         $air = KualitasAir::whereDate('waktu', '=', $date)->first();
//     }

//     return response()->json([
//         "success" => true,
//         "message" => "KualitasAir List",
//         "data" => ($air)
//     ]);
// }

// public function update(Request $request, KualitasAir $product)
// {
//     $input = $request->all();
//     $validator = Validator::make($input, ['name' => 'required', 'detail' => 'required']);
//     if ($validator->fails()) {
//         return $this->sendError('Validation Error.', $validator->errors());
//     }
//     $product->name = $input['name'];
//     $product->detail = $input['detail'];
//     $product->save();
//     return response()->json(["success" => true, "message" => "KualitasAir updated successfully.", "data" => $product]);
// }
// public function destroy(KualitasAir $product)
// {
//     $product->delete();
//     return response()->json(["success" => true, "message" => "KualitasAir deleted successfully.", "data" => $product]);
// }

// public function between(Request $request)
// {
//     // $air = KualitasAir::all(['id', 'waktu', 'pH']);
//     $from = date($request->query('from'));
//     $to = date($request->query('to'));
//     $column = $request->query('column');

//     $air = KualitasAir::whereBetween('waktu', [$from, $to])->orderBy('waktu', 'ASC')->get(['id', 'waktu', $column]);

//     return response()->json([
//         "success" => true,
//         "message" => "KualitasAir List",
//         "data" => ($air)
//     ]);
// }
}
