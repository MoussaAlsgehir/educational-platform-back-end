<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResource;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WithdrawalRequest;
use App\Notifications\GeneralNotification;
use App\Services\WalletService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
                $student->notify(new GeneralNotification(
                "Wallet Credited",
                "Your wallet has been credited with {$request->amount} points successfully by the platform administration.",
                "wallet_topup"
            ));

            return ApiResource::sendResponse("Wallet topped up successfully.", [
                'student_name' => $student->name,
                'new_balance'  => $student->wallet->balance
            ], 200);

        } catch (\Exception $e) {
            return ApiResource::sendResponse("Failed to top up wallet.", null, 500);
        }
    }

    public function pending()
    {
        $requests = WithdrawalRequest::where('status', 'pending')->with('instructor')->latest()->get();
        return ApiResource::sendResponse("Pending withdrawal requests.", $requests);
    }

    public function approve(WithdrawalRequest $withdrawal, WalletService $walletService)
    {
        if ($withdrawal->status !== 'pending') {
            return ApiResource::sendResponse("This request has already been processed.", null, 400);
        }

        try {
            DB::transaction(function () use ($withdrawal, $walletService) {
                $walletService->deductPoints(
                    $withdrawal->instructor,
                    $withdrawal->amount,
                    'withdrawal_deduction',
                    "Withdrawal request #{$withdrawal->id} approved."
                );

                $withdrawal->update(['status' => 'approved']);
            });
                 $withdrawal->instructor->notify(new GeneralNotification(
                "Withdrawal Approved",
                "Your withdrawal request for {$withdrawal->amount} points has been approved and deducted from your balance.",
                "withdrawal_approved"
            ));

            return ApiResource::sendResponse("Withdrawal approved and balance deducted.", $withdrawal);

        } catch (Exception $e) {
            return ApiResource::sendResponse($e->getMessage(), null, 400);
        }
    }

    public function reject(Request $request, WithdrawalRequest $withdrawal)
    {
        $request->validate([
            'admin_notes' => 'required|string|max:255'
        ]);

        if ($withdrawal->status !== 'pending') {
            return ApiResource::sendResponse("This request has already been processed.", null, 400);
        }

        $withdrawal->update([
            'status' => 'rejected',
            'admin_notes' => $request->admin_notes
        ]);

        return ApiResource::sendResponse("Withdrawal request rejected.", $withdrawal);
    }

        /**
     * تقرير مالي مفصل للمنصة (يحسب الليرات السورية بناءً على تسعير النقاط)
     */
    public function getFinancialReport()
    {
        // 1. إجمالي النقاط المباعة للطلاب (عمليات شحن المحفظة)
        $totalPointsSold = Transaction::where('direction', 'credit')
            ->where('transaction_type', 'manual_top_up')
            ->sum('amount');

        // 2. إجمالي النقدية الداخلة للمنصة (النقاط المباعة × 10 ليرة)
        $totalCashRevenue = $totalPointsSold * 10;

        // 3. إجمالي النقاط التي تم إنفاقها لشراء كورسات (أساس حساب الربح)
        $totalPointsSpent = Transaction::where('direction', 'debit')
            ->where('transaction_type', 'course_purchase')
            ->sum('amount');

        // 4. صافي ربح المنصة من الكورسات (النقاط المنفقة × 2 ليرة)
        $platformNetProfit = $totalPointsSpent * 2;

        // 5. إجمالي أرباح المدرسين بالنقاط (النقاط المحولة لحساباتهم)
        $instructorEarningsPoints = Transaction::where('direction', 'credit')
            ->where('transaction_type', 'course_earnings')
            ->sum('amount');

        // 6. إجمالي النقاط التي طلب المدرسون سحبها (وتمت الموافقة عليها)
        $totalWithdrawnPoints = WithdrawalRequest::where('status', 'approved')->sum('amount');

        // 7. إجمالي النقدية الخارجة من المنصة للمدرسين (النقاط المسحوبة × 8 ليرة)
        $totalCashPaidOut = $totalWithdrawnPoints * 8;

        // 8. إجمالي النقاط المتداولة حالياً في محافظ المنصة كلها (طلاب + مدرسين)
        $systemPointsBalance = Wallet::sum('balance');

        return [
            'revenue' => [
                'points_sold'         => $totalPointsSold,
                'cash_revenue'        => $totalCashRevenue,       // النقد الذي يجب أن يكون بحساب المنصة البنكي
            ],
            'profits' => [
                'points_spent'        => $totalPointsSpent,
                'platform_net_profit' => $platformNetProfit,      // صافي ربح المنصة من الكورسات (2 ليرة للنقطة)
            ],
            'instructors' => [
                'total_earnings_points' => $instructorEarningsPoints,
                'pending_balance_points' => $instructorEarningsPoints - $totalWithdrawnPoints, // أرباح لم تُسحب بعد
                'points_withdrawn'      => $totalWithdrawnPoints,
                'cash_paid_out'         => $totalCashPaidOut,     // النقد الذي تم دفعه فعلياً للمدرسين
            ],
            'system_balance' => [
                'total_points_in_wallets' => $systemPointsBalance // إجمالي النقاط الموجودة حالياً بكل المحافظ
            ]
        ];
    }

        public function transactions(Request $request)
    {
        $transactions = Transaction::with('wallet.user')
            ->filterByUser($request->user_id)
            ->filterByType($request->transaction_type)
            ->filterByDirection($request->direction)
            ->filterByAmount($request->min_amount, $request->max_amount)
            ->filterByDateRange($request->from_date, $request->to_date)
            ->latest()
            ->paginate(15);

        return ApiResource::sendResponse("Transactions retrieved successfully.", [
            'transactions' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page'    => $transactions->lastPage(),
                'per_page'     => $transactions->perPage(),
                'total'        => $transactions->total(),
                'has_more'     => $transactions->hasMorePages(),
            ]
        ]);
    }
}
