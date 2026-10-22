<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function index()
    {
        $users = User::latest()->get();
        return view('admin.users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'role' => 'required|in:admin,purchasing,cashier',
            'phone' => 'nullable|string|max:50',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'status' => 'active',
            'password' => Hash::make($validated['password']),
        ]);

        AuditLog::log('user_created', User::class, $user->id, null, ['username' => $user->username, 'role' => $user->role], 'Created user account');

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'User account created successfully!',
            ]);
        }

        return redirect()->route('admin.users.index')->with('success', 'User account created successfully!');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|unique:users,username,' . $user->id,
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,purchasing,cashier',
            'phone' => 'nullable|string|max:50',
            'status' => 'required|in:active,inactive',
            'password' => 'nullable|string|min:6',
        ]);

        $old = ['name' => $user->name, 'role' => $user->role, 'status' => $user->status];

        $user->name = $validated['name'];
        $user->username = $validated['username'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];
        $user->phone = $validated['phone'] ?? null;
        $user->status = $validated['status'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        AuditLog::log('user_updated', User::class, $user->id, $old, ['name' => $user->name, 'role' => $user->role, 'status' => $user->status], 'Updated user account');

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'User account updated successfully!',
            ]);
        }

        return redirect()->route('admin.users.index')->with('success', 'User account updated successfully!');
    }

    public function destroy(Request $request, User $user)
    {
        // Prevent deleting own account
        if (auth()->id() === $user->id) {
            $msg = 'You cannot delete your own account while logged in.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                ], 422);
            }
            return back()->with('error', $msg);
        }

        // Prevent deleting the only remaining admin account
        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            $msg = 'Cannot delete this user because it is the only remaining Administrator account in the system.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                ], 422);
            }
            return back()->with('error', $msg);
        }

        $old = [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
        ];
        $userName = $user->name;

        $user->delete();

        AuditLog::log(
            'user_deleted',
            User::class,
            $user->id,
            $old,
            null,
            "Deleted user account: {$userName}"
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "User account '{$userName}' deleted successfully!",
            ]);
        }

        return redirect()->route('admin.users.index')->with('success', "User account '{$userName}' deleted successfully!");
    }
}
