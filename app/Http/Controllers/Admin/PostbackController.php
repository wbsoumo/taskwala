<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PostbackIpWhitelist;
use App\Models\PostbackLog;
use App\Models\PostbackProvider;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PostbackController extends Controller
{
    public function providers()
    {
        $providers = PostbackProvider::withCount(['ipWhitelists', 'logs'])->get();

        return view('admin.postbacks.providers', compact('providers'));
    }

    public function storeProvider(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:postback_providers'],
            'auth_method' => ['required', Rule::in(['shared_secret', 'api_key', 'hmac_signature', 'provider_token', 'ip_only'])],
            'secret_key' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $provider = PostbackProvider::create($validated);

        AuditService::log(
            action: 'postback_provider_created',
            entityType: PostbackProvider::class,
            entityId: $provider->id,
            newValues: $provider->toArray(),
            actorType: 'admin',
            actorId: auth('admin')->id()
        );

        return redirect()->route('admin.postbacks.providers')->with('success', 'Postback provider created.');
    }

    public function ipWhitelists()
    {
        $whitelists = PostbackIpWhitelist::with('provider')->latest()->paginate(20);
        $providers = PostbackProvider::where('status', 'active')->get();

        return view('admin.postbacks.ip_whitelists', compact('whitelists', 'providers'));
    }

    public function storeIpWhitelist(Request $request)
    {
        $validated = $request->validate([
            'postback_provider_id' => ['required', 'exists:postback_providers,id'],
            'ip_address' => ['required', 'string', 'max:45'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $ip = PostbackIpWhitelist::create($validated);

        AuditService::log(
            action: 'ip_whitelist_added',
            entityType: PostbackIpWhitelist::class,
            entityId: $ip->id,
            newValues: $ip->toArray(),
            actorType: 'admin',
            actorId: auth('admin')->id()
        );

        return redirect()->route('admin.postbacks.ip_whitelists')->with('success', 'IP whitelist entry added.');
    }

    public function logs(Request $request)
    {
        $query = PostbackLog::with('provider');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('request_id', 'like', "%{$search}%")
                  ->orWhere('source_ip', 'like', "%{$search}%")
                  ->orWhere('rejection_reason', 'like', "%{$search}%");
            });
        }

        $logs = $query->latest('created_at')->paginate(25);

        return view('admin.postbacks.logs', compact('logs'));
    }
}
