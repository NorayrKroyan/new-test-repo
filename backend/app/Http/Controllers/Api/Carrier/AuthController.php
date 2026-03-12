<?php

namespace App\Http\Controllers\Api\Carrier;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
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
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'usdot' => ['nullable', 'string', 'max:255'],
            'mc_number' => ['nullable', 'string', 'max:255'],
            'carrier_class' => ['nullable', 'string', 'max:255'],
            'insurance_status' => ['nullable', 'string', 'max:255'],
            'truck_count' => ['nullable', 'integer'],
            'trailer_count' => ['nullable', 'integer'],
        ]);

        $result = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['contact_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'carrier',
                'is_active' => false,
                'must_change_password' => false,
            ]);

            $carrier = Carrier::create([
                'user_id' => $user->id,
                'company_name' => $data['company_name'],
                'contact_name' => $data['contact_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'usdot' => $data['usdot'] ?? null,
                'mc_number' => $data['mc_number'] ?? null,
                'carrier_class' => $data['carrier_class'] ?? null,
                'insurance_status' => $data['insurance_status'] ?? null,
                'truck_count' => $data['truck_count'] ?? null,
                'trailer_count' => $data['trailer_count'] ?? null,
                'status' => 'pending_review',
            ]);

            return compact('user', 'carrier');
        });

        return response()->json([
            'ok' => true,
            'message' => 'Registration submitted. Please wait for admin activation.',
            'user' => $result['user'],
            'carrier' => $result['carrier'],
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->with('carrierProfile')
            ->where('email', $data['email'])
            ->where('role', 'carrier')
            ->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        if (!$user->is_active || !$user->carrierProfile || $user->carrierProfile->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Your carrier account is pending admin activation.'],
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
            'carrier' => $authUser->carrierProfile,
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->carrierProfile) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        return response()->json([
            'user' => $user,
            'carrier' => $user->carrierProfile,
        ]);
    }

    public function dashboard(Request $request)
    {
        $carrier = $request->user()->carrierProfile;

        return response()->json([
            'user' => $request->user(),
            'carrier' => $carrier,
            'cards' => [
                'truck_count' => $carrier?->truck_count ?? 0,
                'trailer_count' => $carrier?->trailer_count ?? 0,
                'status' => $carrier?->status ?? 'pending_review',
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $carrier = $user->carrierProfile;

        $data = $request->validate([
            'contact_name' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'usdot' => ['nullable', 'string', 'max:255'],
            'mc_number' => ['nullable', 'string', 'max:255'],
            'carrier_class' => ['nullable', 'string', 'max:255'],
            'insurance_status' => ['nullable', 'string', 'max:255'],
            'truck_count' => ['nullable', 'integer'],
            'trailer_count' => ['nullable', 'integer'],
        ]);

        DB::transaction(function () use ($user, $carrier, $data) {
            $user->update([
                'name' => $data['contact_name'],
                'email' => $data['email'],
            ]);

            $carrier?->update([
                'company_name' => $data['company_name'],
                'contact_name' => $data['contact_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'usdot' => $data['usdot'] ?? null,
                'mc_number' => $data['mc_number'] ?? null,
                'carrier_class' => $data['carrier_class'] ?? null,
                'insurance_status' => $data['insurance_status'] ?? null,
                'truck_count' => $data['truck_count'] ?? null,
                'trailer_count' => $data['trailer_count'] ?? null,
            ]);
        });

        return response()->json([
            'ok' => true,
            'user' => $user->fresh(),
            'carrier' => $user->carrierProfile()->first(),
        ]);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->carrierProfile) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($data['password']),
            'must_change_password' => false,
        ]);

        return response()->json([
            'ok' => true,
            'user' => $user->fresh(),
            'carrier' => $user->carrierProfile,
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
