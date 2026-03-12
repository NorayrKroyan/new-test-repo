<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CarrierController extends Controller
{
    private const DEFAULT_TEMP_PASSWORD = 'ChangeMe123!';

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $rows = Carrier::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('company_name', 'like', "%{$q}%")
                        ->orWhere('contact_name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('city', 'like', "%{$q}%")
                        ->orWhere('state', 'like', "%{$q}%")
                        ->orWhere('usdot', 'like', "%{$q}%")
                        ->orWhere('mc_number', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json($rows);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_name' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address_1' => ['nullable', 'string', 'max:255'],
            'address_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:50'],
            'usdot' => ['nullable', 'string', 'max:255'],
            'mc_number' => ['nullable', 'string', 'max:255'],
            'carrier_class' => ['nullable', 'string', 'max:255'],
            'insurance_status' => ['nullable', 'string', 'max:255'],
            'truck_count' => ['nullable', 'integer'],
            'trailer_count' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', Rule::in(['pending_review', 'active', 'inactive'])],
            'notes' => ['nullable', 'string'],
        ]);

        $row = DB::transaction(function () use ($data) {
            $carrier = Carrier::create([
                ...$data,
                'status' => $data['status'] ?? 'pending_review',
            ]);

            $this->syncCarrierUser($carrier);

            return $carrier->fresh('user');
        });

        return response()->json($row, 201);
    }

    public function show(Carrier $carrier)
    {
        return response()->json($carrier->load('user'));
    }

    public function update(Request $request, Carrier $carrier)
    {
        $data = $request->validate([
            'company_name' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address_1' => ['nullable', 'string', 'max:255'],
            'address_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:50'],
            'usdot' => ['nullable', 'string', 'max:255'],
            'mc_number' => ['nullable', 'string', 'max:255'],
            'carrier_class' => ['nullable', 'string', 'max:255'],
            'insurance_status' => ['nullable', 'string', 'max:255'],
            'truck_count' => ['nullable', 'integer'],
            'trailer_count' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', Rule::in(['pending_review', 'active', 'inactive'])],
            'notes' => ['nullable', 'string'],
        ]);

        $row = DB::transaction(function () use ($carrier, $data) {
            $carrier->update($data);

            $this->syncCarrierUser($carrier);

            return $carrier->fresh('user');
        });

        return response()->json($row);
    }

    public function destroy(Carrier $carrier)
    {
        DB::transaction(function () use ($carrier) {
            if ($carrier->user) {
                $carrier->user->delete();
            }

            $carrier->delete();
        });

        return response()->json([
            'ok' => true,
        ]);
    }

    private function syncCarrierUser(Carrier $carrier): void
    {
        $user = $carrier->user;
        $status = $carrier->status;
        $name = trim((string) ($carrier->contact_name ?: $carrier->company_name ?: ''));
        $email = trim((string) ($carrier->email ?? ''));

        if ($user) {
            if ($email !== '') {
                $this->assertUniqueUserEmail($email, $user->id);
                $user->email = $email;
            }

            if ($name !== '') {
                $user->name = $name;
            }

            $user->role = 'carrier';
            $user->is_active = $status === 'active';
            $user->save();
        }

        if ($status !== 'active') {
            return;
        }

        if (!$user) {
            if ($email === '') {
                throw ValidationException::withMessages([
                    'email' => ['Active carrier must have an email address.'],
                ]);
            }

            if ($name === '') {
                throw ValidationException::withMessages([
                    'contact_name' => ['Active carrier must have a contact name or company name.'],
                ]);
            }

            $this->assertUniqueUserEmail($email);

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(self::DEFAULT_TEMP_PASSWORD),
                'role' => 'carrier',
                'is_active' => true,
                'must_change_password' => true,
            ]);

            $carrier->user()->associate($user);
            $carrier->save();
        }
    }

    private function assertUniqueUserEmail(string $email, ?int $ignoreUserId = null): void
    {
        $query = User::query()->where('email', $email);

        if ($ignoreUserId) {
            $query->where('id', '!=', $ignoreUserId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'email' => ['This email is already used by another user account.'],
            ]);
        }
    }
}
