<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerPayout;
use App\Models\PayoutRequest;
use App\Models\WalletTransaction;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinanceController extends Controller
{
    public function ledger(Request $request)
    {
        $transactions = WalletTransaction::with(['user', 'conversion.campaign'])
            ->latest()
            ->paginate(25);

        return view('admin.finance.ledger', compact('transactions'));
    }

    public function customerPayouts(Request $request)
    {
        $payouts = CustomerPayout::with(['conversion.campaign', 'conversion.user'])
            ->latest()
            ->paginate(25);

        return view('admin.finance.customer_payouts', compact('payouts'));
    }

    public function affiliatePayouts(Request $request)
    {
        $payoutRequests = collect();
        if (Schema::hasTable('payout_requests')) {
            $query = PayoutRequest::with(['user', 'processedByAdmin']);

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $payoutRequests = $query->latest()->paginate(25)->withQueryString();
        }

        return view('admin.finance.affiliate_payouts', compact('payoutRequests'));
    }

    public function processAffiliatePayout(Request $request, int $id)
    {
        $payoutRequest = PayoutRequest::with('user')->findOrFail($id);

        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $admin = auth('admin')->user();
        $action = $validated['action'];

        return DB::transaction(function () use ($payoutRequest, $action, $validated, $admin) {
            if ($payoutRequest->status !== 'pending') {
                return redirect()->back()->with('error', 'This payout request has already been processed.');
            }

            if ($action === 'approve') {
                $payoutRequest->update([
                    'status' => 'approved',
                    'transaction_id' => $validated['transaction_id'] ?? ('TXN_' . strtoupper(bin2hex(random_bytes(6)))),
                    'admin_notes' => $validated['admin_notes'] ?? 'Payout processed and transferred via UPI.',
                    'processed_at' => now(),
                    'processed_by' => $admin?->id,
                ]);

                // Update corresponding debit transaction reference
                WalletTransaction::where('user_id', $payoutRequest->user_id)
                    ->where('type', 'payout_request')
                    ->where('status', 'pending')
                    ->where('amount', $payoutRequest->amount)
                    ->latest()
                    ->first()
                    ?->update(['status' => 'completed', 'reference' => $payoutRequest->transaction_id ?? 'APPROVED']);

                AuditService::log(
                    action: 'payout_request_approved',
                    entityType: PayoutRequest::class,
                    entityId: $payoutRequest->id,
                    newValues: [
                        'transaction_id' => $payoutRequest->transaction_id,
                        'amount' => $payoutRequest->amount,
                    ],
                    actorType: 'admin',
                    actorId: $admin?->id
                );

                return redirect()->back()->with('success', 'Payout request approved! Settlement recorded with Transaction ID: ' . $payoutRequest->transaction_id);
            } else {
                // Reject payout and refund balance back to user's wallet
                $wallet = \App\Models\Wallet::where('user_id', $payoutRequest->user_id)->lockForUpdate()->first();
                if ($wallet) {
                    $wallet->balance += (float) $payoutRequest->amount;
                    $wallet->save();
                }

                $payoutRequest->update([
                    'status' => 'rejected',
                    'admin_notes' => $validated['admin_notes'] ?? 'Payout request rejected by admin. Balance refunded.',
                    'processed_at' => now(),
                    'processed_by' => $admin?->id,
                ]);

                // Record refund transaction
                WalletTransaction::create([
                    'user_id' => $payoutRequest->user_id,
                    'type' => 'payout_refund',
                    'direction' => 'credit',
                    'amount' => $payoutRequest->amount,
                    'balance_before' => $wallet ? ($wallet->balance - $payoutRequest->amount) : 0,
                    'balance_after' => $wallet ? $wallet->balance : 0,
                    'reference' => 'REFUND_' . strtoupper(substr($payoutRequest->public_id, 0, 8)),
                    'description' => "Payout request rejected. Refunded ₹" . number_format($payoutRequest->amount, 2),
                    'status' => 'completed',
                ]);

                AuditService::log(
                    action: 'payout_request_rejected',
                    entityType: PayoutRequest::class,
                    entityId: $payoutRequest->id,
                    actorType: 'admin',
                    actorId: $admin?->id
                );

                return redirect()->back()->with('success', 'Payout request rejected and funds refunded to affiliate wallet.');
            }
        });
    }
}
