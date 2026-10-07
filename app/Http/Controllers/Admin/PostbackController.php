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
    public function globalPostback()
    {
        $globalSecret = config('postback.global_secret', env('POSTBACK_SECRET', 'taskwala_postback_secret_key_2026'));
        $globalUrl = url('/api/v1/postback/global');
        $globalLogs = PostbackLog::whereNull('postback_provider_id')->latest('created_at')->take(10)->get();

        return view('admin.postbacks.global', compact('globalSecret', 'globalUrl', 'globalLogs'));
    }

    public function offerWisePostback()
    {
        $campaigns = \App\Models\Campaign::with('postbackProvider')
            ->latest()
            ->paginate(15);

        $providers = PostbackProvider::where('status', 'active')->get();

        return view('admin.postbacks.offer_wise', compact('campaigns', 'providers'));
    }

    public function testPostbackForm()
    {
        $recentClicks = \App\Models\Click::with('campaign')->latest('created_at')->take(10)->get();
        $providers = PostbackProvider::where('status', 'active')->get();

        return view('admin.postbacks.test', compact('recentClicks', 'providers'));
    }

    public function sendTestPostback(Request $request)
    {
        $validated = $request->validate([
            'provider_slug' => ['required', 'string'],
            'click_id' => ['required', 'string'],
            'conversion_id' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['approved', 'pending', 'rejected'])],
            'secret' => ['nullable', 'string'],
        ]);

        $url = url('/api/v1/postback/' . $validated['provider_slug']);
        $params = [
            'click_id' => $validated['click_id'],
            'conversion_id' => $validated['conversion_id'] ?? ('TEST_TX_' . rand(1000, 9999)),
            'status' => $validated['status'],
        ];

        if (!empty($validated['secret'])) {
            $params['secret'] = $validated['secret'];
        }

        try {
            $response = \Illuminate\Support\Facades\Http::get($url, $params);

            $result = [
                'status_code' => $response->status(),
                'body' => $response->json() ?? $response->body(),
                'target_url' => $url,
                'method' => 'GET',
                'params' => $params,
            ];

            return redirect()->route('admin.postbacks.test')->with('test_result', $result)->with('success', 'Test GET S2S Postback fired successfully!');
        } catch (\Exception $e) {
            return redirect()->route('admin.postbacks.test')->withErrors(['test_error' => 'Failed to fire GET S2S Postback: ' . $e->getMessage()]);
        }
    }

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
            'postback_provider_id' => ['nullable', 'exists:postback_providers,id'],
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
