<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $this->authorize('manage_facility_users');

        $users = User::with('facility', 'roles')->orderBy('name')->paginate(15);
        $roles = Role::orderBy('name')->pluck('name');

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function create()
    {
        $this->authorize('manage_facility_users');

        $facilities = Facility::orderBy('name')->get();
        $roles = Role::orderBy('name')->get();

        return view('admin.users.create', compact('facilities', 'roles'));
    }

    public function store(Request $request)
    {
        $this->authorize('manage_facility_users');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'facility_id' => 'required|exists:facilities,id',
            'role' => 'required|string|exists:roles,name',
            'status' => 'required|string|in:active,inactive',
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'facility_id' => $validated['facility_id'],
                'status' => $validated['status'],
                'email_verified_at' => now(),
            ]);

            $user->assignRole($validated['role']);

            AuditLog::create([
                'action' => 'create',
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'user_id' => auth()->id(),
                'description' => 'User created: ' . $validated['name'] . ' (' . $validated['email'] . ')',
            ]);

            DB::commit();

            return redirect()->route('users.index')->with('success', 'User created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('User creation failed', ['error' => $e->getMessage()]);

            return redirect()->back()->with('error', 'Failed to create user. Please try again.');
        }
    }

    public function edit(User $user)
    {
        $this->authorize('manage_facility_users');

        $facilities = Facility::orderBy('name')->get();
        $roles = Role::orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'facilities', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('manage_facility_users');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'facility_id' => 'required|exists:facilities,id',
            'role' => 'required|string|exists:roles,name',
            'status' => 'required|string|in:active,inactive',
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            DB::beginTransaction();

            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'facility_id' => $validated['facility_id'],
                'status' => $validated['status'],
            ]);

            if (!empty($validated['password'])) {
                $user->update(['password' => Hash::make($validated['password'])]);
            }

            $user->syncRoles([$validated['role']]);

            AuditLog::create([
                'action' => 'update',
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'user_id' => auth()->id(),
                'description' => 'User updated: ' . $user->name . ' (' . $user->email . ')',
            ]);

            DB::commit();

            return redirect()->route('users.index')->with('success', 'User updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('User update failed', ['error' => $e->getMessage(), 'user_id' => $user->id]);

            return redirect()->back()->with('error', 'Failed to update user. Please try again.');
        }
    }

    public function destroy(User $user)
    {
        $this->authorize('manage_facility_users');

        AuditLog::create([
            'action' => 'delete',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'user_id' => auth()->id(),
            'description' => 'User deleted: ' . $user->name . ' (' . $user->email . ')',
        ]);

        try {
            $user->delete();

            return redirect()->route('users.index')->with('success', 'User deleted successfully.');
        } catch (\Exception $e) {
            \Log::error('User deletion failed', ['error' => $e->getMessage(), 'user_id' => $user->id]);

            return redirect()->back()->with('error', 'Failed to delete user. Please try again.');
        }
    }

    public function toggleStatus(User $user)
    {
        $this->authorize('manage_facility_users');

        $user->update([
            'status' => $user->status === 'active' ? 'inactive' : 'active',
        ]);

        AuditLog::create([
            'action' => 'toggle_status',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'user_id' => auth()->id(),
            'description' => 'User status changed to ' . $user->status . ': ' . $user->name . ' (' . $user->email . ')',
        ]);

        $message = $user->status === 'active' ? 'User activated successfully.' : 'User deactivated successfully.';

        return redirect()->route('users.index')->with('success', $message);
    }
}
