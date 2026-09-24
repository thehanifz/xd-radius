<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function doku(Request $request, PaymentService $payments)
    {
        $rawBody = $request->getContent();
        $path = '/' . ltrim($request->path(), '/');

        try {
            $payments->processWebhook($request->headers->all(), $rawBody, $path);
            return response()->json(['status' => 'OK']);
        } catch (\RuntimeException $e) {
            report($e);
            return response()->json(['status' => 'ERROR'], 400);
        }
    }
}
