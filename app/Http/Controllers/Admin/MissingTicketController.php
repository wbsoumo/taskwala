<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MissingConversionTicket;
use App\Services\LedgerService;
use App\Services\NotificationDispatcherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MissingTicketController extends Controller
{
    protected LedgerService $ledgerService;
    protected NotificationDispatcherService $notifications;

    public function __construct(LedgerService $ledgerService, NotificationDispatcherService $notifications)
    {
        $this->ledgerService = $ledgerService;
        $this->notifications = $notifications;
    }

    public function index(Request $request)
    {
        $query = MissingConversionTicket::with(['user', 'campaign', 'resolvedByAdmin']);

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('order_reference_id', 'like', "%{$search}%")
                    ->orWhere('click_id', 'like', "%{$search}%")
                    ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('campaign', fn($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        $tickets = $query->latest()->paginate(20)->withQueryString();

        return view('admin.tickets.index', compact('tickets'));
    }

    public function show(int $id)
    {
        $ticket = MissingConversionTicket::with(['user.wallet', 'campaign', 'resolvedByAdmin'])->findOrFail($id);

        return view('admin.tickets.show', compact('ticket'));
    }

    public function updateStatus(Request $request, int $id)
    {
        $ticket = MissingConversionTicket::with(['user', 'campaign'])->findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:under_review,approved,rejected'],
            'approved_payout' => ['nullable', 'numeric', 'min:0'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $admin = auth('admin')->user();
        $targetStatus = $validated['status'];
        $campaignName = $ticket->campaign?->name ?? 'Campaign';

        return DB::transaction(function () use ($ticket, $targetStatus, $validated, $admin, $campaignName) {
            $approvedPayout = null;

            if ($targetStatus === 'approved') {
                $approvedPayout = isset($validated['approved_payout']) && $validated['approved_payout'] > 0
                    ? (float) $validated['approved_payout']
                    : (float) ($ticket->campaign?->default_affiliate_payout ?? 0.0);

                if ($ticket->status !== 'approved' && $approvedPayout > 0) {
                    // Credit user's wallet
                    $this->ledgerService->recordTransaction(
                        user: $ticket->user,
                        type: 'affiliate_commission',
                        direction: 'credit',
                        amount: $approvedPayout,
                        reference: 'TICKET_CLAIM_' . $ticket->id,
                        description: "Missing conversion ticket #{$ticket->id} approved by admin for {$campaignName} (Ref: {$ticket->order_reference_id})"
                    );
                }
            }

            $ticket->update([
                'status' => $targetStatus,
                'approved_payout' => $approvedPayout,
                'admin_notes' => $validated['admin_notes'] ?? null,
                'resolved_at' => now(),
                'resolved_by' => $admin?->id,
            ]);

            // Dispatch Push Notification
            try {
                if ($targetStatus === 'approved') {
                    $this->notifications->sendToUser(
                        user: $ticket->user,
                        title: "✅ Claim Approved: {$campaignName}!",
                        body: "₹" . number_format($approvedPayout, 0) . " has been credited to your wallet for missing lead (Ref: {$ticket->order_reference_id}).",
                        deeplink: "/wallet",
                        imageUrl: $ticket->campaign?->logo
                    );
                } elseif ($targetStatus === 'rejected') {
                    $this->notifications->sendToUser(
                        user: $ticket->user,
                        title: "❌ Claim Rejected: {$campaignName}",
                        body: "Your missing lead claim (Ref: {$ticket->order_reference_id}) could not be verified. " . ($validated['admin_notes'] ?? ''),
                        deeplink: "/reports",
                        imageUrl: $ticket->campaign?->logo
                    );
                } else {
                    $this->notifications->sendToUser(
                        user: $ticket->user,
                        title: "Claim Under Review: {$campaignName}",
                        body: "Your missing lead claim is currently being verified with the advertiser.",
                        deeplink: "/reports"
                    );
                }
            } catch (\Throwable $ne) {
                // Ignore push failure
            }

            return redirect()->route('admin.tickets.show', $ticket->id)
                ->with('success', "Ticket #{$ticket->id} updated to " . ucfirst($targetStatus) . " successfully.");
        });
    }
}
