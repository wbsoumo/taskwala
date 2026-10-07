<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AffiliateLink;
use App\Services\AuditService;
use Illuminate\Http\Request;

class LinkManagementController extends Controller
{
    public function index()
    {
        $user = auth('web')->user();

        $links = AffiliateLink::with('campaign')
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(15);

        return view('user.links.index', compact('links'));
    }

    public function disable(string $publicId)
    {
        $user = auth('web')->user();

        $link = AffiliateLink::where('public_id', $publicId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $link->update(['status' => 'disabled']);

        AuditService::log(
            action: 'affiliate_link_disabled',
            entityType: AffiliateLink::class,
            entityId: $link->id,
            actorType: 'user',
            actorId: $user->id
        );

        return redirect()->route('user.links.index')->with('success', 'Link disabled successfully.');
    }
}
