<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\TopUpRequest;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class WalletController extends Controller
{
    public function show(Request $request)
    {
        $wallet = $request->user()->wallet;

        if (! $wallet) {
            return ResponseHelper::jsonResponse([], 'Wallet not found', 404);
        }

        $transactions = Transaction::where('wallet_id', $wallet->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return ResponseHelper::jsonResponse([
            'balance' => $wallet->balance,
            'transactions' => $transactions,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function topUp(TopUpRequest $request)
    {
        $user = $request->user();
        $wallet = $user->wallet;

        if (! $wallet) {
            $wallet = Wallet::create([
                'user_id' => $user->id,
                'balance' => 0,
                'is_active' => true,
            ]);
        }

        if (! $wallet || ! $wallet->is_active) {
            return ResponseHelper::jsonResponse([], 'Wallet not found or inactive', 422);
        }

        DB::transaction(function () use ($wallet, $request) {
            $amount = $request->amount;
            $balanceBefore = $wallet->balance;

            $wallet->increment('balance', $amount);

            Transaction::create([
                'wallet_id' => $wallet->id,
                'order_id' => null,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $amount,
                'type' => 'deposit',
                'status' => 'completed',
            ]);
        });

        return ResponseHelper::jsonResponse(['balance' => $wallet->fresh()->balance], 'Top up successful');
    }
}
