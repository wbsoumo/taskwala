<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function index()
    {
        $user = auth('web')->user();

        return view('user.profile.index', compact('user'));
    }

    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth('web')->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile_number' => ['required', 'string', 'max:20'],
            'upi_id' => ['nullable', 'string', 'max:255'],
            'upi_holder_name' => ['nullable', 'string', 'max:255'],
            'current_password' => ['nullable', 'string'],
            'new_password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if ($request->filled('new_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return redirect()->back()->withErrors(['current_password' => 'Current password does not match our records.']);
            }
            $user->password = Hash::make($request->new_password);
        }

        $user->name = $validated['name'];
        $user->mobile_number = $validated['mobile_number'];
        $user->upi_id = $validated['upi_id'] ?? null;
        $user->upi_holder_name = $validated['upi_holder_name'] ?? null;
        $user->save();

        AuditService::log(
            action: 'profile_updated',
            entityType: User::class,
            entityId: $user->id,
            actorType: 'user',
            actorId: $user->id
        );

        return redirect()->route('user.profile.index')->with('success', 'Profile updated successfully.');
    }
}
