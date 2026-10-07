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
        $transactions = WalletTransaction::where('user_id', $user->id)->latest()->paginate(20);

        $payoutRequests = collect();
        if (Schema::hasTable('payout_requests')) {
            $payoutRequests = PayoutRequest::where('user_id', $user->id)->latest()->paginate(10);
        }

        return response()->json([
            'wallet' => $user->wallet,
            'transactions' => $transactions,
            'payout_requests' => $payoutRequests,
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
            'message' => 'UPI ID updated successfully.',
            'upi_id' => $user->upi_id,
            'upi_holder_name' => $user->upi_holder_name,
        ]);
    }

    public function requestPayout(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user()->load('wallet');

        if (empty($user->upi_id)) {
            return response()->json([
                'message' => 'Please configure your valid UPI ID before requesting a payout settlement.',
            ], 422);
        }

        $availableBalance = (float) ($user->wallet->balance ?? 0.00);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', "max:{$availableBalance}"],
        ]);

        $requestedAmount = (float) $validated['amount'];

        return DB::transaction(function () use ($user, $requestedAmount) {
            $wallet = \App\Models\Wallet::where('user_id', $user->id)->lockForUpdate()->first();

            if ((float) $wallet->balance < $requestedAmount) {
                return response()->json(['message' => 'Insufficient wallet balance.'], 422);
            }

            if (!Schema::hasTable('payout_requests')) {
                return response()->json(['message' => 'Payout requests database table missing on server.'], 500);
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
                'message' => 'Payout request submitted successfully.',
                'payout_request' => $payoutRequest,
            ], 201);
        });
    }
}
