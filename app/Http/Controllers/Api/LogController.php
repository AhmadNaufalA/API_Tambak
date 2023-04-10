<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Log;
use App\Models\LogRusak;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function allLogs()
    {
        $logs = Log::all();

        return [
            "success" => true,
            "messageHandler" => "Log List",
            "data" => $logs
        ];
    }
    public function allLogRusaks()
    {
        $logRusaks = LogRusak::all();

        return [
            "success" => true,
            "messageHandler" => "LogRusak List",
            "data" => $logRusaks
        ];
    }


    public function getLogsByTambakId($id, Request $request)
    {
        $logs = Log::where('id_tambak', $id)->orderBy('waktu', 'DESC')->get()->toArray();

        return response()->json(
            [
                "success" => true,
                "messageHandler" => "Log list",
                "data" => $logs
            ]
        );
    }
    public function getLogRusaksByTambakId($id, Request $request)
    {
        $logRusaks = LogRusak::where('id_tambak', $id)->orderBy('waktu', 'DESC')->get()->toArray();

        return response()->json(
            [
                "success" => true,
                "messageHandler" => "Log Rusak list",
                "data" => $logRusaks
            ]
        );
    }
}