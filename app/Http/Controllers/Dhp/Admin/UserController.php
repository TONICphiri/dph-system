<?php

namespace App\Http\Controllers\Dhp\Admin;

use App\Enums\DhpRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DhpAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * DHP user administration. Manages issuer, verifier and admin accounts
 * only; citizen portal accounts originate in the issuer registration flow.
 * No deletions: deactivation preserves history.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.trim($request->input('search')).'%';
                $query->where(fn ($inner) => $inner
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term));
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('dhp.admin.users.index', ['users' => $users, 'search' => (string) $request->input('search', '')]);
    }

    public function create(): View
    {
        return view('dhp.admin.users.form', [
            'user' => new User(['role' => DhpRole::Issuer, 'is_active' => true]),
            'roles' => [DhpRole::Issuer, DhpRole::Verifier, DhpRole::Admin],
            'method' => 'POST',
            'action' => route('dhp.admin.users.store'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            // Email is required here so the account can use the normal
            // password-reset workflow later (no temporary-password emails).
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users,phone'],
            'role' => ['required', Rule::enum(DhpRole::class), Rule::notIn([DhpRole::Citizen->value])],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'],
            'is_active' => (bool) ($data['is_active'] ?? true),
            'status' => 'active',
            'must_change_password' => true,
            'password' => Hash::make(Str::random(40)),
        ]);

        DhpAuditLogger::log(
            user: $request->user(),
            action: 'dhp_user_created',
            entityType: 'user',
            entityId: $user->id,
            details: ['role' => $user->role->value, 'is_active' => $user->is_active],
            ipAddress: $request->ip(),
        );

        return redirect()->route('dhp.admin.users.index')
            ->with('success', 'User created and pending activation. The holder sets a password through the normal password reset process.');
    }

    public function edit(User $user): View
    {
        return view('dhp.admin.users.form', [
            'user' => $user,
            'roles' => [DhpRole::Issuer, DhpRole::Verifier, DhpRole::Admin],
            'method' => 'PUT',
            'action' => route('dhp.admin.users.update', $user),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($user->id)],
            'role' => ['required', Rule::enum(DhpRole::class)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // Citizen-linked logins must not be repurposed through generic management.
        if ($user->citizenProfile && $data['role'] !== DhpRole::Citizen->value) {
            return back()->withInput()->with('error', 'This login is linked to a citizen passport and its role cannot be changed here.');
        }
        if ($data['role'] === DhpRole::Citizen->value && ! $user->citizenProfile) {
            return back()->withInput()->with('error', 'Citizen portal accounts are created only through citizen registration.');
        }

        $activating = ! $user->is_active && ($data['is_active'] ?? false);
        $deactivating = $user->is_active && ! ($data['is_active'] ?? false);

        if ($deactivating && ! $this->anotherActiveAdminRemains($user)) {
            return back()->withInput()->with('error', 'This is the final active administrator account. It cannot be deactivated.');
        }

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'],
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        DhpAuditLogger::log(
            user: $request->user(),
            action: 'dhp_user_updated',
            entityType: 'user',
            entityId: $user->id,
            details: ['role' => $user->role->value, 'is_active' => $user->is_active],
            ipAddress: $request->ip(),
        );

        if ($activating || $deactivating) {
            DhpAuditLogger::log(
                user: $request->user(),
                action: $activating ? 'dhp_user_activated' : 'dhp_user_deactivated',
                entityType: 'user',
                entityId: $user->id,
                details: ['role' => $user->role->value, 'is_active' => $user->is_active],
                ipAddress: $request->ip(),
            );
        }

        return redirect()->route('dhp.admin.users.index')->with('success', 'User updated.');
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        if ($user->is_active && ! $this->anotherActiveAdminRemains($user)) {
            return back()->with('error', 'This is the final active administrator account. It cannot be deactivated.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        DhpAuditLogger::log(
            user: $request->user(),
            action: $user->is_active ? 'dhp_user_activated' : 'dhp_user_deactivated',
            entityType: 'user',
            entityId: $user->id,
            details: ['role' => $user->role->value, 'is_active' => $user->is_active],
            ipAddress: $request->ip(),
        );

        return back()->with('success', $user->is_active ? 'User activated.' : 'User deactivated. Their access is denied immediately.');
    }

    /**
     * True when at least one OTHER active admin remains besides this user.
     */
    private function anotherActiveAdminRemains(User $user): bool
    {
        if ($user->role !== DhpRole::Admin || ! $user->is_active) {
            return true;
        }

        return User::query()
            ->where('role', DhpRole::Admin)
            ->where('is_active', true)
            ->whereKeyNot($user->id)
            ->exists();
    }
}
