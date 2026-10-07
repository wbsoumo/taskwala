<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $inputs = $request->except(['_token', '_method']);

        foreach ($inputs as $key => $value) {
            Setting::set($key, $value);
        }

        AuditService::log(
            action: 'settings_updated',
            entityType: Setting::class,
            newValues: $inputs,
            actorType: 'admin',
            actorId: auth('admin')->id()
        );

        return redirect()->route('admin.settings.index')->with('success', 'Application settings updated.');
    }
}
