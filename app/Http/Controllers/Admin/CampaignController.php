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
            'postback_provider_id' => ['nullable', 'exists:postback_providers,id'],
        ]);

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
            'postback_provider_id' => ['nullable', 'exists:postback_providers,id'],
        ]);

        // Handle File Upload if provided
        if ($request->hasFile('logo_file')) {
            $path = $request->file('logo_file')->store('campaigns', 'public');
            $validated['logo_url'] = Storage::url($path);
        }

        if (empty($validated['campaign_type'])) {
            $validated['campaign_type'] = 'cpa';
        }

        unset($validated['logo_file']);

        $this->campaignService->updateCampaign($campaign, $validated, auth('admin')->id());

        return redirect()->route('admin.campaigns.show', $campaign)->with('success', 'Campaign updated successfully.');
    }
}
