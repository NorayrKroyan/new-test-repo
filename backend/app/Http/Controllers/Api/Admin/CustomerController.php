<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    private const DEFAULT_TEMP_PASSWORD = 'ChangeMe123!';

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $rows = Customer::query()
            ->with('user:id,name,email,role,is_active,must_change_password')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('company_name', 'like', "%{$q}%")
                        ->orWhere('contact_name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('city', 'like', "%{$q}%")
                        ->orWhere('state', 'like', "%{$q}%");
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
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(['pending_review', 'active', 'inactive'])],
            'notes' => ['nullable', 'string'],
        ]);

        $status = $data['status'] ?? 'pending_review';
        $plainPassword = $data['password'] ?? self::DEFAULT_TEMP_PASSWORD;
        $mustChangePassword = empty($data['password']);

        $row = DB::transaction(function () use ($data, $status, $plainPassword, $mustChangePassword) {
            $user = User::create([
                'name' => $data['contact_name'],
                'email' => $data['email'],
                'password' => Hash::make($plainPassword),
                'role' => 'customer',
                'is_active' => $status === 'active',
                'must_change_password' => $mustChangePassword,
            ]);

            return Customer::create([
                'user_id' => $user->id,
                'company_name' => $data['company_name'] ?? null,
                'contact_name' => $data['contact_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'status' => $status,
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return response()->json($row->load('user:id,name,email,role,is_active,must_change_password'), 201);
    }

    public function show(Customer $customer)
    {
        return response()->json($customer->load('user:id,name,email,role,is_active,must_change_password'));
    }

    public function update(Request $request, Customer $customer)
    {
        $user = $customer->user;

        $data = $request->validate([
            'company_name' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(['pending_review', 'active', 'inactive'])],
            'notes' => ['nullable', 'string'],
        ]);

        $row = DB::transaction(function () use ($customer, $user, $data) {
            $nextStatus = $data['status'] ?? $customer->status;
            $nextName = $data['contact_name'];
            $nextEmail = $data['email'];

            if ($user) {
                $user->name = $nextName;
                $user->email = $nextEmail;
                $user->role = 'customer';
                $user->is_active = $nextStatus === 'active';

                if (!empty($data['password'])) {
                    $user->password = Hash::make($data['password']);
                    $user->must_change_password = false;
                }

                $user->save();
            } else {
                if ($nextStatus === 'active') {
                    $createdUser = User::create([
                        'name' => $nextName,
                        'email' => $nextEmail,
                        'password' => Hash::make(self::DEFAULT_TEMP_PASSWORD),
                        'role' => 'customer',
                        'is_active' => true,
                        'must_change_password' => true,
                    ]);

                    $customer->user()->associate($createdUser);
                    $customer->save();
                }
            }

            $customer->update([
                'company_name' => $data['company_name'] ?? null,
                'contact_name' => $nextName,
                'email' => $nextEmail,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'status' => $nextStatus,
                'notes' => $data['notes'] ?? null,
            ]);

            return $customer->fresh()->load('user:id,name,email,role,is_active,must_change_password');
        });

        return response()->json($row);
    }

    public function destroy(Customer $customer)
    {
        DB::transaction(function () use ($customer) {
            if ($customer->user) {
                $customer->user->delete();
            }

            $customer->delete();
        });

        return response()->json([
            'ok' => true,
        ]);
    }
}
