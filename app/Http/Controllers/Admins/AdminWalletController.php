<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResource;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\Request;

class AdminWalletController extends Controller
{
    public function topUp(Request $request, WalletService $walletService)
    {
        $request->validate([
            'student_email' => 'required|email|exists:users,email',
            'amount'         => 'required|numeric|min:1',
            'description'    => 'nullable|string|max:255'
        ]);

        $student = User::where('email', $request->student_email)->first();

        try {
            $transaction = $walletService->addPoints(
                $student,
                $request->amount,
                'manual_top_up',
                $request->description ?? "Manual top-up by Admin"
            );

            return ApiResource::sendResponse("Wallet topped up successfully.", [
                'student_name' => $student->name,
                'new_balance'  => $student->wallet->balance
            ], 200);

        } catch (\Exception $e) {
            return ApiResource::sendResponse("Failed to top up wallet.", null, 500);
        }
    }
}
