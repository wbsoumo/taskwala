<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSlider;
use App\Models\Campaign;
use App\Models\Deal;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AppSliderController extends Controller
{
    /**
     * Display listing of all app sliders and banners.
     */
    public function index(Request $request)
    {
        $query = AppSlider::query()->orderBy('order', 'asc')->orderBy('id', 'desc');

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('media_type', $request->type);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }

        $sliders = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => AppSlider::count(),
            'active' => AppSlider::where('is_active', true)->count(),
            'videos' => AppSlider::where('media_type', 'video')->count(),
            'images' => AppSlider::where('media_type', 'image')->count(),
        ];

        return view('admin.sliders.index', compact('sliders', 'stats'));
    }

    /**
     * Show form for creating a new slider.
     */
    public function create()
    {
        $campaigns = Campaign::active()->orderBy('name', 'asc')->get(['id', 'name', 'category', 'logo_url', 'payout_type', 'affiliate_payout']);
        $deals = Deal::active()->orderBy('id', 'desc')->take(30)->get(['id', 'public_id', 'title', 'price', 'category']);

        $commonRoutes = [
            ['route' => '/campaigns', 'label' => 'Offers / Campaigns Screen'],
            ['route' => '/deals', 'label' => 'Trending Deals Screen'],
            ['route' => '/referral', 'label' => 'Refer & Earn Dashboard'],
            ['route' => '/links', 'label' => 'My Generated Links'],
            ['route' => '/reports', 'label' => 'Reports & Performance'],
            ['route' => '/wallet', 'label' => 'Wallet & Payouts Screen'],
            ['route' => '/reports/claim', 'label' => 'Missing Conversion Claim'],
            ['route' => '/profile', 'label' => 'My Profile Screen'],
        ];

        return view('admin.sliders.create', compact('campaigns', 'deals', 'commonRoutes'));
    }

    /**
     * Store a newly created slider.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'badge_text' => 'nullable|string|max:50',
            'media_type' => 'required|in:image,video',
            'media_file' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif,mp4,mov,webm|max:51200', // up to 50MB
            'media_url_input' => 'nullable|string|max:1000',
            'thumbnail_file' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:5120',
            'action_type' => 'required|in:none,campaign,deal,app_route,external_url',
            'campaign_id' => 'nullable|required_if:action_type,campaign',
            'deal_id' => 'nullable|required_if:action_type,deal',
            'app_route' => 'nullable|required_if:action_type,app_route|string|max:255',
            'external_url' => 'nullable|required_if:action_type,external_url|url|max:1000',
            'auto_play_video' => 'nullable|boolean',
            'loop_video' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        // Resolve Media URL
        $mediaUrl = null;
        if ($request->hasFile('media_file')) {
            $path = $request->file('media_file')->store('sliders', 'public');
            $mediaUrl = 'storage/' . $path;
        } elseif (!empty($validated['media_url_input'])) {
            $mediaUrl = trim($validated['media_url_input']);
        }

        if (empty($mediaUrl)) {
            return back()->withInput()->withErrors(['media_file' => 'Please upload a media file or provide a direct media URL.']);
        }

        // Resolve Thumbnail URL
        $thumbUrl = null;
        if ($request->hasFile('thumbnail_file')) {
            $thumbPath = $request->file('thumbnail_file')->store('sliders/thumbs', 'public');
            $thumbUrl = 'storage/' . $thumbPath;
        }

        // Resolve Action Value
        $actionValue = match ($validated['action_type']) {
            'campaign' => (string) ($validated['campaign_id'] ?? ''),
            'deal' => (string) ($validated['deal_id'] ?? ''),
            'app_route' => (string) ($validated['app_route'] ?? ''),
            'external_url' => (string) ($validated['external_url'] ?? ''),
            default => null,
        };

        $slider = AppSlider::create([
            'title' => $validated['title'] ?? null,
            'subtitle' => $validated['subtitle'] ?? null,
            'badge_text' => $validated['badge_text'] ?? null,
            'media_type' => $validated['media_type'],
            'media_url' => $mediaUrl,
            'thumbnail_url' => $thumbUrl,
            'action_type' => $validated['action_type'],
            'action_value' => $actionValue,
            'auto_play_video' => $request->boolean('auto_play_video', true),
            'loop_video' => $request->boolean('loop_video', true),
            'order' => (int) ($validated['order'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditService::log(
            action: 'app_slider_created',
            entityType: AppSlider::class,
            entityId: $slider->id,
            actorType: 'admin',
            actorId: auth('admin')->id() ?? 1,
            details: ['title' => $slider->title, 'media_type' => $slider->media_type, 'action_type' => $slider->action_type]
        );

        return redirect()->route('admin.sliders.index')->with('success', 'App slider created successfully!');
    }

    /**
     * Show form for editing a slider.
     */
    public function edit($id)
    {
        $slider = AppSlider::findOrFail($id);
        $campaigns = Campaign::active()->orderBy('name', 'asc')->get(['id', 'name', 'category', 'logo_url', 'payout_type', 'affiliate_payout']);
        $deals = Deal::active()->orderBy('id', 'desc')->take(30)->get(['id', 'public_id', 'title', 'price', 'category']);

        $commonRoutes = [
            ['route' => '/campaigns', 'label' => 'Offers / Campaigns Screen'],
            ['route' => '/deals', 'label' => 'Trending Deals Screen'],
            ['route' => '/referral', 'label' => 'Refer & Earn Dashboard'],
            ['route' => '/links', 'label' => 'My Generated Links'],
            ['route' => '/reports', 'label' => 'Reports & Performance'],
            ['route' => '/wallet', 'label' => 'Wallet & Payouts Screen'],
            ['route' => '/reports/claim', 'label' => 'Missing Conversion Claim'],
            ['route' => '/profile', 'label' => 'My Profile Screen'],
        ];

        return view('admin.sliders.edit', compact('slider', 'campaigns', 'deals', 'commonRoutes'));
    }

    /**
     * Update an existing slider.
     */
    public function update(Request $request, $id)
    {
        $slider = AppSlider::findOrFail($id);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'badge_text' => 'nullable|string|max:50',
            'media_type' => 'required|in:image,video',
            'media_file' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif,mp4,mov,webm|max:51200',
            'media_url_input' => 'nullable|string|max:1000',
            'thumbnail_file' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:5120',
            'action_type' => 'required|in:none,campaign,deal,app_route,external_url',
            'campaign_id' => 'nullable|required_if:action_type,campaign',
            'deal_id' => 'nullable|required_if:action_type,deal',
            'app_route' => 'nullable|required_if:action_type,app_route|string|max:255',
            'external_url' => 'nullable|required_if:action_type,external_url|url|max:1000',
            'auto_play_video' => 'nullable|boolean',
            'loop_video' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $updates = [
            'title' => $validated['title'] ?? null,
            'subtitle' => $validated['subtitle'] ?? null,
            'badge_text' => $validated['badge_text'] ?? null,
            'media_type' => $validated['media_type'],
            'action_type' => $validated['action_type'],
            'auto_play_video' => $request->boolean('auto_play_video', true),
            'loop_video' => $request->boolean('loop_video', true),
            'order' => (int) ($validated['order'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
        ];

        // Replace Media URL if provided
        if ($request->hasFile('media_file')) {
            $path = $request->file('media_file')->store('sliders', 'public');
            $updates['media_url'] = 'storage/' . $path;
        } elseif (!empty($validated['media_url_input'])) {
            $updates['media_url'] = trim($validated['media_url_input']);
        }

        // Replace Thumbnail if provided
        if ($request->hasFile('thumbnail_file')) {
            $thumbPath = $request->file('thumbnail_file')->store('sliders/thumbs', 'public');
            $updates['thumbnail_url'] = 'storage/' . $thumbPath;
        }

        // Action Value
        $updates['action_value'] = match ($validated['action_type']) {
            'campaign' => (string) ($validated['campaign_id'] ?? ''),
            'deal' => (string) ($validated['deal_id'] ?? ''),
            'app_route' => (string) ($validated['app_route'] ?? ''),
            'external_url' => (string) ($validated['external_url'] ?? ''),
            default => null,
        };

        $slider->update($updates);

        AuditService::log(
            action: 'app_slider_updated',
            entityType: AppSlider::class,
            entityId: $slider->id,
            actorType: 'admin',
            actorId: auth('admin')->id() ?? 1,
            details: ['title' => $slider->title, 'is_active' => $slider->is_active]
        );

        return redirect()->route('admin.sliders.index')->with('success', 'Slider updated successfully!');
    }

    /**
     * Toggle active state.
     */
    public function toggle($id)
    {
        $slider = AppSlider::findOrFail($id);
        $slider->is_active = !$slider->is_active;
        $slider->save();

        return response()->json([
            'success' => true,
            'is_active' => $slider->is_active,
            'message' => $slider->is_active ? 'Slider activated.' : 'Slider paused.',
        ]);
    }

    /**
     * Delete a slider.
     */
    public function destroy($id)
    {
        $slider = AppSlider::findOrFail($id);

        // Delete local files if exist
        if ($slider->media_url && str_starts_with($slider->media_url, 'storage/sliders/')) {
            $file = str_replace('storage/', '', $slider->media_url);
            Storage::disk('public')->delete($file);
        }
        if ($slider->thumbnail_url && str_starts_with($slider->thumbnail_url, 'storage/sliders/')) {
            $file = str_replace('storage/', '', $slider->thumbnail_url);
            Storage::disk('public')->delete($file);
        }

        $slider->delete();

        return back()->with('success', 'Slider deleted successfully.');
    }
}
