<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResource;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    public function balance()
    {
        $wallet = Auth::user()->wallet;

        return ApiResource::sendResponse("Wallet balance retrieved.", [
            'balance' => $wallet->balance
        ]);
    }
}
