<?php

namespace App\Services;

use App\Models\Conversion;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LedgerService
{
    /**
     * Credit or debit user wallet with double-entry transaction record.
     */
    public function recordTransaction(
        User $user,
        string $type,
        string $direction,
        float $amount,
        ?Conversion $conversion = null,
        ?string $reference = null,
        ?string $description = null
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Transaction amount must be greater than zero.');
        }

        return DB::transaction(function () use ($user, $type, $direction, $amount, $conversion, $reference, $description) {
            /** @var Wallet $wallet */
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();

            if (!$wallet) {
                $wallet = Wallet::create([
                    'user_id' => $user->id,
                    'balance' => 0.00,
                    'pending_balance' => 0.00,
                    'total_withdrawn' => 0.00,
                    'currency' => config('platform.currency', 'INR'),
                ]);
            }

            $balanceBefore = (float) $wallet->balance;

            if ($direction === 'credit') {
                $balanceAfter = $balanceBefore + $amount;
                $wallet->balance = $balanceAfter;
            } elseif ($direction === 'debit') {
                if ($balanceBefore < $amount) {
                    throw new InvalidArgumentException('Insufficient wallet balance.');
                }
                $balanceAfter = $balanceBefore - $amount;
                $wallet->balance = $balanceAfter;
            } else {
                throw new InvalidArgumentException("Invalid transaction direction: {$direction}");
            }

            $wallet->save();

            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'conversion_id' => $conversion ? $conversion->id : null,
                'type' => $type,
                'direction' => $direction,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference' => $reference ?? ('TXN_' . strtoupper(bin2hex(random_bytes(8)))),
                'description' => $description,
                'status' => 'completed',
            ]);

            return $transaction;
        });
    }

    /**
     * Add to pending balance when conversion is in 'pending' status.
     */
    public function addPendingBalance(User $user, float $amount): Wallet
    {
        return DB::transaction(function () use ($user, $amount) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
            $wallet->pending_balance += $amount;
            $wallet->save();
            return $wallet;
        });
    }

    /**
     * Release pending balance into approved balance upon conversion approval.
     */
    public function approvePendingBalance(User $user, float $amount, Conversion $conversion): WalletTransaction
    {
        return DB::transaction(function () use ($user, $amount, $conversion) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
            if ($wallet->pending_balance >= $amount) {
                $wallet->pending_balance -= $amount;
                $wallet->save();
            }

            return $this->recordTransaction(
                user: $user,
                type: 'affiliate_commission',
                direction: 'credit',
                amount: $amount,
                conversion: $conversion,
                reference: 'CONV_' . $conversion->public_id,
                description: "Approved commission payout for conversion #{$conversion->id}"
            );
        });
    }
}
