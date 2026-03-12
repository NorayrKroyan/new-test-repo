<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CarrierController extends Controller
{
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

        $row = Carrier::create([
            ...$data,
            'status' => $data['status'] ?? 'pending_review',
        ]);

        if ($row->user) {
            $row->user->is_active = $row->status === 'active';
            $row->user->save();
        }

        return response()->json($row, 201);
    }

    public function show(Carrier $carrier)
    {
        return response()->json($carrier);
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

        DB::transaction(function () use ($carrier, $data) {
            $carrier->update($data);

            if ($carrier->user) {
                $carrier->user->is_active = $carrier->status === 'active';
                $carrier->user->save();
            }
        });

        return response()->json($carrier->fresh());
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
}
