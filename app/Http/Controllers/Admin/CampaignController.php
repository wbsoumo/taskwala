<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\User;
use App\Services\CampaignService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CampaignController extends Controller
{
    protected CampaignService $campaignService;

    public function __construct(CampaignService $campaignService)
    {
        $this->campaignService = $campaignService;
    }

    public function index(Request $request)
    {
        $query = Campaign::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('advertiser_name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $campaigns = $query->latest()->paginate(15);

        return view('admin.campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        $providers = \App\Models\PostbackProvider::where('status', 'active')->get();
        return view('admin.campaigns.create', compact('providers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:campaigns'],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'logo_url' => ['nullable', 'string', 'max:255'],
            'logo_file' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'theme' => ['required', Rule::in(['gradient_blue', 'dark_glass', 'emerald', 'clean_minimal'])],
            'advertiser_name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'campaign_type' => ['nullable', 'string', 'max:50'],
            'landing_url' => ['required', 'string', 'max:2000'],
            'conversion_event' => ['required', 'string', 'max:100'],
            'advertiser_payout' => ['required', 'numeric', 'min:0'],
            'default_affiliate_payout' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'status' => ['required', Rule::in(['draft', 'active', 'paused', 'expired', 'archived'])],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'terms' => ['nullable', 'string'],
            'kpi_requirements' => ['nullable', 'string'],
            'duplicate_conversion_rules' => ['nullable', 'string'],
            'check_duplicate_ip' => ['nullable', 'boolean'],
            'postback_provider_id' => ['nullable', 'exists:postback_providers,id'],
            'postback_secret_key' => ['nullable', 'string', 'max:64'],
        ]);

        $validated['check_duplicate_ip'] = $request->boolean('check_duplicate_ip');

        // Handle File Upload if provided
        if ($request->hasFile('logo_file')) {
            $path = $request->file('logo_file')->store('campaigns', 'public');
            $validated['logo_url'] = Storage::url($path);
        }

        if (empty($validated['campaign_type'])) {
            $validated['campaign_type'] = 'cpa';
        }

        unset($validated['logo_file']);

        $campaign = $this->campaignService->createCampaign($validated, auth('admin')->id());

        // Broadcast Push Notification if active
        if ($campaign->status === 'active') {
            try {
                app(\App\Services\NotificationDispatcherService::class)->broadcast(
                    title: "🔥 New Offer: {$campaign->name}",
                    body: "Earn up to ₹" . number_format($campaign->default_affiliate_payout, 0) . " per task. Check it out now!",
                    deeplink: "/campaigns",
                    imageUrl: $campaign->logo,
                    adminId: auth('admin')->id()
                );
            } catch (\Throwable $ne) {
                \Illuminate\Support\Facades\Log::warning("Campaign broadcast push failed: " . $ne->getMessage());
            }
        }

        return redirect()->route('admin.campaigns.show', $campaign)->with('success', 'Campaign created successfully.');
    }

    public function show(Campaign $campaign)
    {
        $campaign->load(['affiliateAllocations.user', 'links.user']);
        $users = User::where('status', 'active')->get();

        return view('admin.campaigns.show', compact('campaign', 'users'));
    }

    public function edit(Campaign $campaign)
    {
        $providers = \App\Models\PostbackProvider::where('status', 'active')->get();
        return view('admin.campaigns.edit', compact('campaign', 'providers'));
    }

    public function update(Request $request, Campaign $campaign)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('campaigns')->ignore($campaign->id)],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'logo_url' => ['nullable', 'string', 'max:255'],
            'logo_file' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'theme' => ['required', Rule::in(['gradient_blue', 'dark_glass', 'emerald', 'clean_minimal'])],
            'advertiser_name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'campaign_type' => ['nullable', 'string', 'max:50'],
            'landing_url' => ['required', 'string', 'max:2000'],
            'conversion_event' => ['required', 'string', 'max:100'],
            'advertiser_payout' => ['required', 'numeric', 'min:0'],
            'default_affiliate_payout' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'status' => ['required', Rule::in(['draft', 'active', 'paused', 'expired', 'archived'])],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'terms' => ['nullable', 'string'],
            'kpi_requirements' => ['nullable', 'string'],
            'duplicate_conversion_rules' => ['nullable', 'string'],
            'check_duplicate_ip' => ['nullable', 'boolean'],
            'postback_provider_id' => ['nullable', 'exists:postback_providers,id'],
            'postback_secret_key' => ['nullable', 'string', 'max:64'],
        ]);

        $validated['check_duplicate_ip'] = $request->boolean('check_duplicate_ip');

        // Handle File Upload if provided
        if ($request->hasFile('logo_file')) {
            $path = $request->file('logo_file')->store('campaigns', 'public');
            $validated['logo_url'] = Storage::url($path);
        }

        if (empty($validated['campaign_type'])) {
            $validated['campaign_type'] = 'cpa';
        }

        unset($validated['logo_file']);

        $previousStatus = $campaign->status;
        $this->campaignService->updateCampaign($campaign, $validated, auth('admin')->id());

        // Broadcast if newly activated
        if ($previousStatus !== 'active' && $campaign->status === 'active') {
            try {
                app(\App\Services\NotificationDispatcherService::class)->broadcast(
                    title: "🚀 Now Live: {$campaign->name}",
                    body: "Earn up to ₹" . number_format($campaign->default_affiliate_payout, 0) . " per completed task. Start sharing today!",
                    deeplink: "/campaigns",
                    imageUrl: $campaign->logo,
                    adminId: auth('admin')->id()
                );
            } catch (\Throwable $ne) {
                \Illuminate\Support\Facades\Log::warning("Campaign activation broadcast push failed: " . $ne->getMessage());
            }
        }

        return redirect()->route('admin.campaigns.show', $campaign)->with('success', 'Campaign updated successfully.');
    }

    public function uploadCollateral(Request $request, Campaign $campaign)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'media_type' => ['required', 'in:image,video,text'],
            'category_preset' => ['nullable', 'in:special_deal,friends_share,cashback_alert,general'],
            'media_file' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,mp4,mov,avi', 'max:51200'],
            'default_caption' => ['nullable', 'string', 'max:5000'],
        ]);

        if ($validated['media_type'] !== 'text' && !$request->hasFile('media_file')) {
            return back()->withErrors(['media_file' => 'A media file (image/video) is required for this media type.'])->withInput();
        }

        if ($validated['media_type'] === 'text' && empty($validated['default_caption'])) {
            return back()->withErrors(['default_caption' => 'Promotional caption message is required for text templates.'])->withInput();
        }

        $filePath = null;
        if ($request->hasFile('media_file')) {
            $path = $request->file('media_file')->store('collaterals', 'public');
            $filePath = Storage::url($path);
        }

        $campaign->collaterals()->create([
            'title' => $validated['title'],
            'media_type' => $validated['media_type'],
            'category_preset' => $validated['category_preset'] ?? 'general',
            'file_path' => $filePath,
            'default_caption' => $validated['default_caption'] ?? null,
            'sort_order' => $campaign->collaterals()->count(),
            'is_active' => true,
        ]);

        return redirect()->route('admin.campaigns.show', $campaign)
            ->with('success', 'Marketing collateral / promotional template added successfully.');
    }

    public function deleteCollateral(int $id)
    {
        $collateral = \App\Models\CampaignCollateral::findOrFail($id);
        $campaignId = $collateral->campaign_id;
        $collateral->delete();

        return redirect()->route('admin.campaigns.show', $campaignId)
            ->with('success', 'Collateral deleted successfully.');
    }
}
