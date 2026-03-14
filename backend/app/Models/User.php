<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'must_change_password',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            if ($user->isForceDeleting()) {
                return;
            }

            if ($user->is_active) {
                $user->newQueryWithoutScopes()
                    ->whereKey($user->getKey())
                    ->update([
                        'is_active' => false,
                    ]);

                $user->is_active = false;
            }
        });

        static::restoring(function (User $user) {
            $user->is_active = false;
        });
    }

    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where('role', 'super_admin');
    }

    public function scopeCarriers(Builder $query): Builder
    {
        return $query->where('role', 'carrier');
    }

    public function scopeCustomers(Builder $query): Builder
    {
        return $query->where('role', 'customer');
    }

    public function carrierProfile()
    {
        return $this->hasOne(Carrier::class);
    }

    public function customerProfile()
    {
        return $this->hasOne(Customer::class);
    }
}
