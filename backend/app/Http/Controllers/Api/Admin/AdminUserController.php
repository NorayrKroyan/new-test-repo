<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $rows = User::query()
            ->where('role', 'super_admin')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json($rows);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'super_admin',
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json($user, 201);
    }

    public function show(User $admin_user)
    {
        return response()->json($admin_user);
    }

    public function update(Request $request, User $admin_user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($admin_user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $admin_user->name = $data['name'];
        $admin_user->email = $data['email'];
        $admin_user->is_active = $data['is_active'] ?? $admin_user->is_active;

        if (!empty($data['password'])) {
            $admin_user->password = Hash::make($data['password']);
        }

        $admin_user->save();

        return response()->json($admin_user);
    }

    public function destroy(User $admin_user)
    {
        $admin_user->delete();

        return response()->json([
            'ok' => true,
        ]);
    }
}
