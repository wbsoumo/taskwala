<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\MissingConversionTicket;
use App\Services\NotificationDispatcherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MissingTicketController extends Controller
{
    /**
     * List user's missing conversion claim tickets.
     */
    public function index(Request $request): JsonResponse
    {
        $tickets = MissingConversionTicket::with('campaign')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'public_id' => $t->public_id,
                    'campaign_id' => $t->campaign_id,
                    'campaign_name' => $t->campaign?->name ?? 'Campaign',
                    'campaign_logo' => $t->campaign?->logo,
                    'category' => $t->campaign?->category ?? 'General',
                    'click_id' => $t->click_id,
                    'order_reference_id' => $t->order_reference_id,
                    'task_date' => $t->task_date?->format('Y-m-d') ?? '',
                    'screenshot_url' => $t->screenshot_url,
                    'notes' => $t->notes,
                    'status' => $t->status,
                    'admin_notes' => $t->admin_notes,
                    'approved_payout' => (float) ($t->approved_payout ?? 0.0),
                    'created_at' => $t->created_at?->format('Y-m-d H:i') ?? '',
                    'resolved_at' => $t->resolved_at?->format('Y-m-d H:i'),
                ];
            });

        return response()->json(['tickets' => $tickets]);
    }

    /**
     * Submit a new missing conversion claim ticket with screenshot proof.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'campaign_id' => ['required'],
            'order_reference_id' => ['required', 'string', 'max:255'],
            'task_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'click_id' => ['nullable', 'string', 'max:255'],
            'screenshot' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:10240'],
        ]);

        // Resolve Campaign by ID or public_id
        $campaign = Campaign::where('id', $validated['campaign_id'])
            ->orWhere('public_id', $validated['campaign_id'])
            ->first();

        if (!$campaign) {
            return response()->json(['success' => false, 'message' => 'Selected campaign not found.'], 404);
        }

        $screenshotPath = null;
        if ($request->hasFile('screenshot')) {
            $path = $request->file('screenshot')->store('tickets', 'public');
            $screenshotPath = Storage::url($path);
        }

        $ticket = MissingConversionTicket::create([
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
            'click_id' => $validated['click_id'] ?? null,
            'order_reference_id' => trim($validated['order_reference_id']),
            'task_date' => $validated['task_date'],
            'screenshot_path' => $screenshotPath,
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
        ]);

        // Send confirmation in-app notification
        try {
            app(NotificationDispatcherService::class)->sendToUser(
                user: $user,
                title: "Ticket Received: Ref #{$ticket->order_reference_id}",
                body: "Your claim for {$campaign->name} has been submitted. Our team will verify and update your wallet within 24-48 hours.",
                deeplink: "/reports"
            );
        } catch (\Throwable $e) {
            // Ignore notification error on submission
        }

        return response()->json([
            'success' => true,
            'message' => 'Missing conversion ticket submitted successfully.',
            'ticket' => [
                'id' => $ticket->id,
                'public_id' => $ticket->public_id,
                'status' => $ticket->status,
                'order_reference_id' => $ticket->order_reference_id,
            ],
        ], 201);
    }
}
