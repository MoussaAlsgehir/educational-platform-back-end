<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Exception;

class WalletService
{

    public function addPoints(User $user, float $amount, string $type, string $description = null): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $type, $description) {

              $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();

            if (!$wallet) {
                throw new Exception("Wallet not found.");
            }

            $wallet->balance += $amount;
            $wallet->save();

            return $this->logTransaction($wallet, 'credit', $amount, $type, $description);
        });
    }

    /**
     * خصم النقاط (عند شراء كورس)
     * ترمي Exception إذا كان الرصيد غير كافي
     */
    public function deductPoints(User $user, float $amount, string $type, string $description = null): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $type, $description) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();

            if (!$wallet) {
                throw new Exception("Wallet not found.");
            }

            if ($wallet->balance < $amount) {
                throw new Exception("Insufficient balance.");
            }

            $wallet->balance -= $amount;
            $wallet->save();

            return $this->logTransaction($wallet, 'debit', $amount, $type, $description);
        });
    }

    /**
     * تسجيل العملية المالية (Audit Log)
     */
    private function logTransaction(Wallet $wallet, string $direction, float $amount, string $type, ?string $description): Transaction
    {
        return Transaction::create([
            'wallet_id'        => $wallet->id,
            'direction'        => $direction,
            'transaction_type' => $type,
            'amount'           => $amount,
            'balance_after'    => $wallet->balance,
            'description'      => $description,
        ]);
    }
}
