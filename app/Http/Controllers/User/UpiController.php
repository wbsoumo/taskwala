<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\Request;

class UpiController extends Controller
{
    public function index()
    {
        $user = auth('web')->user();

        return view('user.upi.index', compact('user'));
    }

    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth('web')->user();

        $validated = $request->validate([
            'upi_id' => ['required', 'string', 'max:255', 'regex:/^[\w\.\-]+@[\w\.\-]+$/i'],
            'upi_holder_name' => ['nullable', 'string', 'max:255'],
        ], [
            'upi_id.regex' => 'Please enter a valid UPI ID (e.g. username@upi or mobile@upi).',
        ]);

        $user->upi_id = $validated['upi_id'];
        $user->upi_holder_name = $validated['upi_holder_name'] ?? null;
        $user->save();

        AuditService::log(
            action: 'upi_details_updated',
            entityType: get_class($user),
            entityId: $user->id,
            actorType: 'user',
            actorId: $user->id
        );

        return redirect()->route('user.upi.index')->with('success', 'UPI / Payout details updated successfully.');
    }
}
