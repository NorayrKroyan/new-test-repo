<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request, string $role)
    {
        abort_unless(in_array($role, $this->allowedRoles(), true), 404);

        config([
            'services.google.redirect' => $this->callbackUrl($role),
        ]);

        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->stateless()
            ->redirect();
    }

    public function callback(Request $request, string $role)
    {
        abort_unless(in_array($role, $this->allowedRoles(), true), 404);

        config([
            'services.google.redirect' => $this->callbackUrl($role),
        ]);

        try {
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->user();
        } catch (\Throwable $e) {
            Log::error('Google OAuth callback failed', [
                'role' => $role,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'app_url' => config('app.url'),
                'frontend_url' => config('app.frontend_url'),
                'callback_url' => $this->callbackUrl($role),
                'full_url' => $request->fullUrl(),
                'query' => $request->query(),
            ]);

            return redirect()->away($this->errorUrl($role, 'google_auth_failed'));
        }

        $email = strtolower(trim((string) $googleUser->getEmail()));
        $googleId = (string) $googleUser->getId();

        if ($email === '') {
            return redirect()->away($this->errorUrl($role, 'google_email_missing'));
        }

        $user = User::query()
            ->with(['carrierProfile', 'customerProfile'])
            ->where('email', $email)
            ->first();

        if (!$user) {
            return redirect()->away($this->errorUrl($role, 'account_not_found'));
        }

        if ($user->google_id && $user->google_id !== $googleId) {
            return redirect()->away($this->errorUrl($role, 'google_account_conflict'));
        }

        if (!$this->matchesRole($user, $role)) {
            return redirect()->away($this->errorUrl($role, 'wrong_account_type'));
        }

        $eligibilityError = $this->eligibilityError($user, $role);
        if ($eligibilityError) {
            return redirect()->away($this->errorUrl($role, $eligibilityError));
        }

        DB::transaction(function () use ($user, $googleUser, $googleId) {
            if (!$user->google_id) {
                $user->google_id = $googleId;
            }

            if (!$user->name && $googleUser->getName()) {
                $user->name = $googleUser->getName();
            }

            $user->avatar = $googleUser->getAvatar();
            $user->last_login_at = now();
            $user->save();
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->away($this->successUrl($role, $user));
    }

    private function allowedRoles(): array
    {
        return ['admin', 'carrier', 'customer'];
    }

    private function callbackUrl(string $role): string
    {
        $base = rtrim((string) config('app.url'), '/');

        return $base . '/api/auth/google/callback/' . $role;
    }

    private function matchesRole(User $user, string $role): bool
    {
        return match ($role) {
            'admin' => $user->role === 'super_admin',
            'carrier' => $user->role === 'carrier',
            'customer' => $user->role === 'customer',
            default => false,
        };
    }

    private function eligibilityError(User $user, string $role): ?string
    {
        if (!$user->is_active) {
            return 'account_inactive';
        }

        return match ($role) {
            'admin' => null,
            'carrier' => !$user->carrierProfile
                ? 'carrier_profile_missing'
                : (($user->carrierProfile->status ?? null) !== 'active' ? 'carrier_not_active' : null),
            'customer' => !$user->customerProfile
                ? 'customer_profile_missing'
                : (($user->customerProfile->status ?? null) !== 'active' ? 'customer_not_active' : null),
            default => 'invalid_role',
        };
    }

    private function successUrl(string $role, User $user): string
    {
        $frontend = rtrim((string) config('app.frontend_url'), '/');

        $path = match ($role) {
            'admin' => '/admin/dashboard',
            'carrier' => $user->must_change_password ? '/carrier/change-password' : '/carrier/dashboard',
            'customer' => $user->must_change_password ? '/customer/change-password' : '/customer/dashboard',
            default => '/login',
        };

        return $frontend . $path . '?google=1';
    }

    private function errorUrl(string $role, string $error): string
    {
        $frontend = rtrim((string) config('app.frontend_url'), '/');

        $path = match ($role) {
            'admin' => '/admin/login',
            'carrier' => '/login?role=carrier',
            'customer' => '/login?role=customer',
            default => '/login',
        };

        $separator = str_contains($path, '?') ? '&' : '?';

        return $frontend . $path . $separator . 'auth_error=' . urlencode($error);
    }
}
