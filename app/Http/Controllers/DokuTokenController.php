<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class DokuTokenController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $token = bin2hex(random_bytes(32));

        return response()->json([
            'responseCode' => '2007300',
            'responseMessage' => 'Successful',
            'accessToken' => $token,
            'tokenType' => 'Bearer',
            'expiresIn' => 900,
        ]);
    }
}
