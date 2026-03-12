<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $rows = Customer::query()
            ->with('user:id,name,email,role,is_active')
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

        $row = DB::transaction(function () use ($data, $status) {
            $user = User::create([
                'name' => $data['contact_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password'] ?? 'ChangeMe123!'),
                'role' => 'customer',
                'is_active' => $status === 'active',
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

        return response()->json($row->load('user:id,name,email,role,is_active'), 201);
    }

    public function show(Customer $customer)
    {
        return response()->json($customer->load('user:id,name,email,role,is_active'));
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

        DB::transaction(function () use ($customer, $user, $data) {
            if ($user) {
                $user->name = $data['contact_name'];
                $user->email = $data['email'];
                $user->is_active = ($data['status'] ?? $customer->status) === 'active';

                if (!empty($data['password'])) {
                    $user->password = Hash::make($data['password']);
                }

                $user->save();
            }

            $customer->update([
                'company_name' => $data['company_name'] ?? null,
                'contact_name' => $data['contact_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'status' => $data['status'] ?? $customer->status,
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return response()->json($customer->fresh()->load('user:id,name,email,role,is_active'));
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
