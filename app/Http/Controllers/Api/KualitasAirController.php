<?php

namespace App\Http\Controllers\Api;
use App\Models\KualitasAir;

use App\Http\Controllers\Controller;
use App\Models\Log;
use App\Models\Tambak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class KualitasAirController extends Controller
{
    // public function index()
    // {
    //     $air = KualitasAir::all();

    //     return response()->json([
    //         "success" => true,
    //         "message" => "KualitasAir List",
    //         "data" => ($air)
    //     ]);
    // }
    public function store(Request $request)
    {
        $input = $request->all();

        $previousKualitasAir = KualitasAir::where("id_tambak", $input['id_tambak'])->orderBy('waktu', 'DESC')->first();

        $kualitasAir = KualitasAir::create($input);
        $messaging = app('firebase.messaging');

        $topic = 'a-topic';

        $tambak = Tambak::find($request->id_tambak);
        $checkers = [
            'pH' => [7, 9],
            'Salinitas' => [15, 25],
            'Suhu' => [26, 30],
            'Ketinggian' => [25, 40],
            'Oksigen' => [4, 8],
            'Kekeruhan' => [25, 40],
        ];

        $margin = [
            'pH' => 5,
            'Salinitas' => 20,
            'Suhu' => 20,
            'Ketinggian' => 20,
            'Oksigen' => 5,
            'Kekeruhan' => 20,
        ];

        $badCheck = [];
        foreach ($checkers as $key => $value) {
            if ($request->all()[$key] < $value[0] || $request->all()[$key] > $value[1]) {
                array_push($badCheck, $key);
            }
        }

        $badMargin = [];
        foreach ($margin as $key => $value) {
            if (abs( $request->all()[$key] - $previousKualitasAir[$key]) >= $value) {
                array_push($badMargin, $key);
            }
        }


        if (count($badCheck) > 0) {

            $badVariablesString = implode(", ", $badCheck);

            $badVariableValues = implode(", ",  array_map(
                function ($k) use ($request){
                    return $request->all()[$k];
                },
                $badCheck
            ));

            Log::insert(
                array_map(function($bad) use($tambak, $checkers) {
                    $isi = "";

                    switch ($bad) {
                        case 'pH':
                            $isi = "Jika ".$bad." kurang dari ".$checkers[$bad][0]." maka perlu diberikan..., apabila lebih dari ".$checkers[$bad][1]." perlu diberi ...";
                            break;
                        case 'Salinitas':
                            $isi = "Jika ".$bad." kurang dari ".$checkers[$bad][0]." maka perlu diberikan..., apabila lebih dari ".$checkers[$bad][1]." perlu diberi ...";
                            break;
                        default:
                            break;
                    }

                    return [
                        "id_tambak" => $tambak->id,
                        "isi" => $isi
                    ];
                }, $badCheck)
            );

            $title =  $badVariablesString. " tidak optimal";
            $body = $badVariablesString . " tidak normal di ".$tambak->name."  yaitu ".$badVariableValues;

            $message = CloudMessage::withTarget('topic', $topic)
                ->withData([
                    'title' => $title,
                    'body' => $body,
                ]); // optional
            ;

            $messaging->send($message);
        }
        if (count($badMargin) > 0) {

            $badVariablesString = implode(", ", $badMargin);

            $title =  "Sensor ".$badVariablesString. " kemungkinan rusak";
            $body ="Pada ".$tambak->name;

            $message = CloudMessage::withTarget('topic', $topic)
                ->withData([
                    'title' => $title,
                    'body' => $body,
                ]); // optional
            ;

            $messaging->send($message);
        }
        return response()->json(["success" => true, "message" => "KualitasAir created successfully.", "data" => $kualitasAir]);
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
