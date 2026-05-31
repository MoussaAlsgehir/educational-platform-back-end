<?php


namespace App\Helpers;

class ApiResource
{

    static function sendResponse( $message = null, $data = null, $code = 200)
    {
        $response = [
            'status' => $code,
            'message' => $message,
            'data' => $data
        ];
        return response()->json($response, $code);
    }
}
