<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use App\Models\WalletTransaction;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WalletController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user()->load('wallet');
        $wallet = $user->wallet ?? \App\Models\Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0.00, 'pending_balance' => 0.00, 'total_withdrawn' => 0.00, 'currency' => 'INR']
        );

        $transactions = WalletTransaction::where('user_id', $user->id)->latest()->paginate(20);

        $payoutRequests = collect();
        if (Schema::hasTable('payout_requests')) {
            $payoutRequests = PayoutRequest::where('user_id', $user->id)->latest()->paginate(10);
        }

        $minThreshold = (float) \App\Models\Setting::get('min_withdrawal_threshold', 500.00);

        // Calculate earnings from immutable conversion snapshots & ledger
        $userConversions = \App\Models\Conversion::where('user_id', $user->id);
        $approvedConversionIds = (clone $userConversions)->where('status', 'approved')->pluck('id');
        $approvedSnapshotsSum = (float) \App\Models\PayoutSnapshot::whereIn('conversion_id', $approvedConversionIds)->sum('affiliate_commission');

        $pendingConversionIds = (clone $userConversions)->where('status', 'pending')->pluck('id');
        $pendingSnapshotsSum = (float) \App\Models\PayoutSnapshot::whereIn('conversion_id', $pendingConversionIds)->sum('affiliate_commission');

        $availableBalance = (float) ($wallet->balance ?? 0.00);
        $totalWithdrawn = (float) ($wallet->total_withdrawn ?? 0.00);
        $pendingBalance = max((float) ($wallet->pending_balance ?? 0.00), $pendingSnapshotsSum);

        $creditedCommissions = (float) WalletTransaction::where('user_id', $user->id)
            ->where('direction', 'credit')
            ->whereIn('type', ['affiliate_commission', 'commission', 'bonus', 'referral_bonus', 'referral_reward', 'adjustment'])
            ->sum('amount');

        $approvedBalance = max($approvedSnapshotsSum, $creditedCommissions, ($availableBalance + $totalWithdrawn));
        $totalEarnings = $approvedBalance + $pendingBalance;

        $walletData = array_merge($wallet->toArray(), [
            'balance' => $availableBalance,
            'available_balance' => $availableBalance,
            'pending_balance' => $pendingBalance,
            'approved_balance' => $approvedBalance,
            'total_earnings' => $totalEarnings,
            'total_paid' => $totalWithdrawn,
            'total_withdrawn' => $totalWithdrawn,
            'upi_id' => $user->upi_id,
            'upi_holder_name' => $user->upi_holder_name,
            'upi_status' => !empty($user->upi_id) ? 'verified' : 'not_added',
        ]);

        return response()->json([
            'wallet' => $walletData,
            'available_balance' => $availableBalance,
            'pending_balance' => $pendingBalance,
            'approved_balance' => $approvedBalance,
            'total_earnings' => $totalEarnings,
            'total_paid' => $totalWithdrawn,
            'upi_id' => $user->upi_id,
            'upi_holder_name' => $user->upi_holder_name,
            'upi_status' => !empty($user->upi_id) ? 'verified' : 'not_added',
            'transactions' => $transactions,
            'payout_requests' => $payoutRequests,
            'min_withdrawal_threshold' => $minThreshold,
        ]);
    }

    public function updateUpi(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'upi_id' => ['required', 'string', 'max:255', 'regex:/^[\w\.\-]+@[\w\.\-]+$/i'],
            'upi_holder_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $user->upi_id = strtolower(trim($validated['upi_id']));
        $user->upi_holder_name = $validated['upi_holder_name'] ?? null;
        $user->save();

        AuditService::log(
            action: 'mobile_upi_updated',
            entityType: get_class($user),
            entityId: $user->id,
            actorType: 'user',
            actorId: $user->id
        );

        return response()->json([
            'success' => true,
            'message' => 'UPI ID updated successfully.',
            'upi_id' => $user->upi_id,
            'upi_holder_name' => $user->upi_holder_name,
        ]);
    }

    public function payoutRequests(Request $request): JsonResponse
    {
        $user = $request->user();
        $payoutRequests = collect();
        if (Schema::hasTable('payout_requests')) {
            $query = PayoutRequest::where('user_id', $user->id);
            if ($request->filled('status')) {
                $status = strtolower($request->status);
                if (in_array($status, ['pending', 'completed', 'approved', 'rejected'])) {
                    if ($status === 'completed' || $status === 'approved') {
                        $query->whereIn('status', ['completed', 'approved']);
                    } else {
                        $query->where('status', $status);
                    }
                }
            }
            $payoutRequests = $query->latest()->paginate(25);
        }

        $minThreshold = (float) \App\Models\Setting::get('min_withdrawal_threshold', 500.00);

        return response()->json([
            'data' => $payoutRequests,
            'min_withdrawal_threshold' => $minThreshold,
        ]);
    }

    public function requestPayout(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user()->load('wallet');

        if (empty($user->upi_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Please configure your valid UPI ID before requesting a payout settlement.',
            ], 422);
        }

        $availableBalance = (float) ($user->wallet->balance ?? 0.00);
        $minThreshold = (float) \App\Models\Setting::get('min_withdrawal_threshold', 500.00);

        if ($availableBalance < $minThreshold) {
            return response()->json([
                'success' => false,
                'message' => 'Minimum withdrawal threshold set by admin is ₹' . number_format($minThreshold, 2) . '. Your available balance is ₹' . number_format($availableBalance, 2) . '.',
                'min_threshold' => $minThreshold,
            ], 422);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', "min:{$minThreshold}", "max:{$availableBalance}"],
        ], [
            'amount.min' => 'Minimum withdrawal request amount is ₹' . number_format($minThreshold, 2) . '.',
            'amount.max' => 'You cannot withdraw more than your available wallet balance of ₹' . number_format($availableBalance, 2) . '.',
        ]);

        $requestedAmount = (float) $validated['amount'];

        return DB::transaction(function () use ($user, $requestedAmount) {
            $wallet = \App\Models\Wallet::where('user_id', $user->id)->lockForUpdate()->first();

            if ((float) $wallet->balance < $requestedAmount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient wallet balance.'
                ], 422);
            }

            if (!Schema::hasTable('payout_requests')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payout requests database table missing on server.'
                ], 500);
            }

            $wallet->balance -= $requestedAmount;
            $wallet->save();

            $payoutRequest = PayoutRequest::create([
                'user_id' => $user->id,
                'amount' => $requestedAmount,
                'upi_id' => $user->upi_id,
                'upi_holder_name' => $user->upi_holder_name,
                'status' => 'pending',
            ]);

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

            return response()->json([
                'success' => true,
                'message' => 'Payout request submitted successfully.',
                'payout_request' => $payoutRequest,
                'new_balance' => (float) $wallet->balance,
            ], 201);
        });
    }
}
