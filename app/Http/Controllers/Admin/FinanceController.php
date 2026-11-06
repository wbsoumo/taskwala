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
        $query = CustomerPayout::with(['conversion.campaign', 'conversion.user', 'processedByAdmin']);

        // Filter by Status
        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'done') {
                $query->whereIn('status', ['done', 'processed']);
            } else {
                $query->where('status', $request->status);
            }
        }

        // Filter by Date Range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search Filter (UPI, Holder Name, Customer Name, Mobile, Transaction ID, Conversion ID, Campaign Name, Affiliate)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('upi_id', 'like', "%{$search}%")
                    ->orWhere('upi_holder_name', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('mobile_number', 'like', "%{$search}%")
                    ->orWhere('transaction_id', 'like', "%{$search}%")
                    ->orWhere('click_id', 'like', "%{$search}%");

                if (is_numeric($search)) {
                    $q->orWhere('conversion_id', (int) $search);
                }

                $q->orWhereHas('conversion.campaign', function ($c) use ($search) {
                    $c->where('name', 'like', "%{$search}%");
                });

                $q->orWhereHas('conversion.user', function ($u) use ($search) {
                    $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            });
        }

        // Summary Statistics for Quick Stats Cards
        $stats = [
            'total_count' => CustomerPayout::count(),
            'total_amount' => (float) CustomerPayout::sum('payout_amount'),
            'pending_count' => CustomerPayout::where('status', 'pending')->count(),
            'pending_amount' => (float) CustomerPayout::where('status', 'pending')->sum('payout_amount'),
            'initiated_count' => CustomerPayout::where('status', 'initiated')->count(),
            'initiated_amount' => (float) CustomerPayout::where('status', 'initiated')->sum('payout_amount'),
            'done_count' => CustomerPayout::whereIn('status', ['done', 'processed'])->count(),
            'done_amount' => (float) CustomerPayout::whereIn('status', ['done', 'processed'])->sum('payout_amount'),
            'failed_count' => CustomerPayout::where('status', 'failed')->count(),
            'failed_amount' => (float) CustomerPayout::where('status', 'failed')->sum('payout_amount'),
            'cancel_count' => CustomerPayout::where('status', 'cancel')->count(),
            'cancel_amount' => (float) CustomerPayout::where('status', 'cancel')->sum('payout_amount'),
        ];

        $payouts = $query->latest()->paginate(25)->withQueryString();

        return view('admin.finance.customer_payouts', compact('payouts', 'stats'));
    }

    public function updateCustomerPayoutStatus(Request $request, int $id)
    {
        $payout = CustomerPayout::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,initiated,done,failed,cancel'],
            'transaction_id' => ['nullable', 'string', 'max:128'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $admin = auth('admin')->user();
            $status = $validated['status'];

            $updateData = ['status' => $status];

            if (Schema::hasColumn('customer_payouts', 'admin_notes')) {
                $updateData['admin_notes'] = $validated['admin_notes'] ?? $payout->admin_notes;
            }
            if (Schema::hasColumn('customer_payouts', 'processed_at')) {
                $updateData['processed_at'] = now();
            }
            if (Schema::hasColumn('customer_payouts', 'processed_by_admin_id')) {
                $updateData['processed_by_admin_id'] = $admin?->id;
            }
            if (Schema::hasColumn('customer_payouts', 'transaction_id')) {
                if ($request->filled('transaction_id')) {
                    $updateData['transaction_id'] = $validated['transaction_id'];
                } elseif ($status === 'done' && empty($payout->transaction_id)) {
                    $updateData['transaction_id'] = 'UPI_' . strtoupper(bin2hex(random_bytes(6)));
                }
            }

            try {
                $payout->update($updateData);
            } catch (\Throwable $dbEx) {
                // If live DB has legacy enum constraint ['pending', 'processed', 'failed'], fallback 'done' to 'processed'
                if ($status === 'done' && isset($updateData['status'])) {
                    $updateData['status'] = 'processed';
                    $payout->update($updateData);
                } else {
                    throw $dbEx;
                }
            }

            return redirect()->route('admin.finance.customer_payouts')
                ->with('success', "Customer Payout #{$id} status updated to " . ucfirst($status) . " successfully.");
        } catch (\Throwable $e) {
            return redirect()->route('admin.finance.customer_payouts')
                ->with('error', "Could not update payout: " . $e->getMessage() . ". Please ensure latest migrations are executed (`php artisan migrate --force`).");
        }
    }

    public function bulkUpdateCustomerPayoutStatus(Request $request)
    {
        $validated = $request->validate([
            'selected_ids' => ['required', 'array', 'min:1'],
            'selected_ids.*' => ['integer', 'exists:customer_payouts,id'],
            'bulk_status' => ['required', 'string', 'in:pending,initiated,done,failed,cancel'],
            'bulk_transaction_id' => ['nullable', 'string', 'max:128'],
            'bulk_admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $admin = auth('admin')->user();
            $targetStatus = $validated['bulk_status'];

            $updateData = ['status' => $targetStatus];

            if (Schema::hasColumn('customer_payouts', 'processed_at')) {
                $updateData['processed_at'] = now();
            }
            if (Schema::hasColumn('customer_payouts', 'processed_by_admin_id')) {
                $updateData['processed_by_admin_id'] = $admin?->id;
            }
            if ($request->filled('bulk_transaction_id') && Schema::hasColumn('customer_payouts', 'transaction_id')) {
                $updateData['transaction_id'] = $validated['bulk_transaction_id'];
            }
            if ($request->filled('bulk_admin_notes') && Schema::hasColumn('customer_payouts', 'admin_notes')) {
                $updateData['admin_notes'] = $validated['bulk_admin_notes'];
            }

            try {
                $affected = CustomerPayout::whereIn('id', $validated['selected_ids'])->update($updateData);
            } catch (\Throwable $dbEx) {
                if ($targetStatus === 'done' && isset($updateData['status'])) {
                    $updateData['status'] = 'processed';
                    $affected = CustomerPayout::whereIn('id', $validated['selected_ids'])->update($updateData);
                } else {
                    throw $dbEx;
                }
            }

            return redirect()->route('admin.finance.customer_payouts')
                ->with('success', "Bulk updated {$affected} customer payout(s) to " . ucfirst($targetStatus) . " successfully.");
        } catch (\Throwable $e) {
            return redirect()->route('admin.finance.customer_payouts')
                ->with('error', "Could not bulk update payouts: " . $e->getMessage() . ". Please ensure latest migrations are executed (`php artisan migrate --force`).");
        }
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
