<?php

namespace App\Http\Controllers\Instructors;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResource;
use App\Models\WithdrawalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WithdrawalController extends Controller
{
    public function index()
    {
        $requests = WithdrawalRequest::where('instructor_id', Auth::id())->latest()->get();
        return ApiResource::sendResponse("Withdrawal requests retrieved.", $requests);
    }

    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1'
        ]);

        $user = Auth::user();

        if ($user->wallet->balance < $request->amount) {
            return ApiResource::sendResponse("Insufficient balance for withdrawal.", null, 422);
        }

        $withdrawal = WithdrawalRequest::create([
            'instructor_id' => $user->id,
            'amount' => $request->amount,
            'status' => 'pending'
        ]);

        return ApiResource::sendResponse("Withdrawal request submitted successfully.", $withdrawal, 201);
    }
}
