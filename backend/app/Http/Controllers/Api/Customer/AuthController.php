<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'contact_name' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
        ]);

        $result = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['contact_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'customer',
                'is_active' => false,
            ]);

            $customer = Customer::create([
                'user_id' => $user->id,
                'company_name' => $data['company_name'],
                'contact_name' => $data['contact_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'status' => 'pending_review',
            ]);

            return compact('user', 'customer');
        });

        return response()->json([
            'ok' => true,
            'message' => 'Registration submitted. Please wait for admin activation.',
            'user' => $result['user'],
            'customer' => $result['customer'],
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->with('customerProfile')
            ->where('email', $data['email'])
            ->where('role', 'customer')
            ->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        if (!$user->is_active || !$user->customerProfile || $user->customerProfile->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Your customer account is pending admin activation.'],
            ]);
        }

        if (!Auth::attempt($data)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $request->session()->regenerate();

        $authUser = $request->user();

        $authUser->forceFill([
            'last_login_at' => now(),
        ])->save();

        return response()->json([
            'user' => $authUser,
            'customer' => $authUser->customerProfile,
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->customerProfile) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        return response()->json([
            'user' => $user,
            'customer' => $user->customerProfile,
        ]);
    }

    public function dashboard(Request $request)
    {
        $user = $request->user();
        $customer = $user?->customerProfile;

        if (!$user || !$customer) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        return response()->json([
            'user' => $user,
            'customer' => $customer,
            'cards' => [
                'status' => $customer->status ?? 'pending_review',
                'jobs_count' => $customer->jobs()->count(),
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $customer = $user?->customerProfile;

        if (!$user || !$customer) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $data = $request->validate([
            'contact_name' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($user, $customer, $data) {
            $user->update([
                'name' => $data['contact_name'],
                'email' => $data['email'],
            ]);

            $customer->update([
                'company_name' => $data['company_name'],
                'contact_name' => $data['contact_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
            ]);
        });

        return response()->json([
            'ok' => true,
            'user' => $user->fresh(),
            'customer' => $user->customerProfile()->first(),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'ok' => true,
        ]);
    }
}
