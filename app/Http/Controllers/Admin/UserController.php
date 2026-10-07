<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::withCount(['links', 'clicks', 'conversions']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mobile_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->latest()->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'mobile_number' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8'],
            'status' => ['required', Rule::in(['active', 'suspended', 'blocked'])],
            'upi_id' => ['nullable', 'string', 'max:255'],
            'upi_holder_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        AuditService::log(
            action: 'user_created',
            entityType: User::class,
            entityId: $user->id,
            newValues: $user->toArray(),
            actorType: 'admin',
            actorId: auth('admin')->id()
        );

        return redirect()->route('admin.users.show', $user)->with('success', 'User created successfully.');
    }

    public function show(User $user)
    {
        $user->load(['wallet', 'links.campaign', 'clicks.campaign', 'conversions.campaign', 'walletTransactions']);

        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'mobile_number' => ['nullable', 'string', 'max:20'],
            'status' => ['required', Rule::in(['active', 'suspended', 'blocked'])],
            'upi_id' => ['nullable', 'string', 'max:255'],
            'upi_holder_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($request->filled('password')) {
            $request->validate(['password' => ['string', 'min:8']]);
            $validated['password'] = Hash::make($request->password);
        }

        $oldValues = $user->toArray();
        $user->update($validated);

        AuditService::log(
            action: 'user_updated',
            entityType: User::class,
            entityId: $user->id,
            oldValues: $oldValues,
            newValues: $user->toArray(),
            actorType: 'admin',
            actorId: auth('admin')->id()
        );

        return redirect()->route('admin.users.show', $user)->with('success', 'User updated successfully.');
    }
}
