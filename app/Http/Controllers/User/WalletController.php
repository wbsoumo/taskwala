<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use App\Models\WalletTransaction;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WalletController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = auth('web')->user();
        $user->load('wallet');

        $transactions = WalletTransaction::with('conversion.campaign')
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(15);

        $payoutRequests = collect();
        if (Schema::hasTable('payout_requests')) {
            $payoutRequests = PayoutRequest::where('user_id', $user->id)
                ->latest()
                ->paginate(10, ['*'], 'payouts_page');
        }

        return view('user.wallet.index', compact('user', 'transactions', 'payoutRequests'));
    }

    public function requestPayout(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth('web')->user();
        $user->load('wallet');

        // Check if UPI ID is configured
        if (empty($user->upi_id)) {
            return redirect()->route('user.upi.index')
                ->with('error', 'Please set up and save your valid UPI ID & bank details first before requesting a payout settlement.');
        }

        $availableBalance = (float) ($user->wallet->balance ?? 0.00);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', "max:{$availableBalance}"],
        ], [
            'amount.max' => 'You cannot request more than your available wallet balance of ₹' . number_format($availableBalance, 2),
            'amount.min' => 'Minimum payout request amount is ₹1.00',
        ]);

        $requestedAmount = (float) $validated['amount'];

        return DB::transaction(function () use ($user, $requestedAmount) {
            $wallet = \App\Models\Wallet::where('user_id', $user->id)->lockForUpdate()->first();

            if ((float) $wallet->balance < $requestedAmount) {
                return redirect()->back()->withErrors(['amount' => 'Insufficient wallet balance for this payout request.']);
            }

            // Deduct available balance and create payout request
            $wallet->balance -= $requestedAmount;
            $wallet->save();

            $payoutRequest = PayoutRequest::create([
                'user_id' => $user->id,
                'amount' => $requestedAmount,
                'upi_id' => $user->upi_id,
                'upi_holder_name' => $user->upi_holder_name,
                'status' => 'pending',
            ]);

            // Create wallet transaction entry
            WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'payout_request',
                'direction' => 'debit',
                'amount' => $requestedAmount,
                'balance_before' => $wallet->balance + $requestedAmount,
                'balance_after' => $wallet->balance,
                'reference' => 'REQ_' . strtoupper(substr($payoutRequest->public_id, 0, 8)),
                'description' => "Payout request submitted to UPI destination: {$user->upi_id}",
                'status' => 'pending',
            ]);

            AuditService::log(
                action: 'payout_request_submitted',
                entityType: PayoutRequest::class,
                entityId: $payoutRequest->id,
                newValues: [
                    'amount' => $requestedAmount,
                    'upi_id' => $user->upi_id,
                ],
                actorType: 'user',
                actorId: $user->id
            );

            return redirect()->route('user.wallet.index')->with('success', 'Payout request of ₹' . number_format($requestedAmount, 2) . ' submitted successfully! Admin will review and process your settlement.');
        });
    }
}
