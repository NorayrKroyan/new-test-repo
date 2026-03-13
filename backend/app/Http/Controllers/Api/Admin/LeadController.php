<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\Lead;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $rows = Lead::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('full_name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('city', 'like', "%{$q}%")
                        ->orWhere('state', 'like', "%{$q}%")
                        ->orWhere('carrier_class', 'like', "%{$q}%")
                        ->orWhere('platform', 'like', "%{$q}%")
                        ->orWhere('ad_name', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => $rows,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'source_name' => ['nullable', 'string', 'max:255'],
            'ad_name' => ['nullable', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:255'],
            'source_created_at' => ['nullable', 'date'],
            'lead_date_choice' => ['nullable', 'string', 'max:255'],
            'insurance_answer' => ['nullable', 'string', 'max:255'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'carrier_class' => ['nullable', 'string', 'max:255'],
            'usdot' => ['nullable', 'string', 'max:255'],
            'truck_count' => ['nullable', 'integer'],
            'trailer_count' => ['nullable', 'integer'],
            'lead_status' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'sold_amount' => ['nullable', 'numeric'],
            'referral_fee' => ['nullable', 'numeric'],
            'sold_at' => ['nullable', 'date'],
        ]);

        $row = Lead::create($data);

        return response()->json($row, 201);
    }

    public function show(Lead $lead)
    {
        return response()->json($lead);
    }

    public function update(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'source_name' => ['nullable', 'string', 'max:255'],
            'ad_name' => ['nullable', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:255'],
            'source_created_at' => ['nullable', 'date'],
            'lead_date_choice' => ['nullable', 'string', 'max:255'],
            'insurance_answer' => ['nullable', 'string', 'max:255'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'carrier_class' => ['nullable', 'string', 'max:255'],
            'usdot' => ['nullable', 'string', 'max:255'],
            'truck_count' => ['nullable', 'integer'],
            'trailer_count' => ['nullable', 'integer'],
            'lead_status' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'sold_amount' => ['nullable', 'numeric'],
            'referral_fee' => ['nullable', 'numeric'],
            'sold_at' => ['nullable', 'date'],
        ]);

        $lead->update($data);

        return response()->json($lead);
    }

    public function destroy(Lead $lead)
    {
        $lead->delete();

        return response()->json([
            'ok' => true,
        ]);
    }

    public function convertToCarrier(Lead $lead)
    {
        $carrier = Carrier::create([
            'company_name' => null,
            'contact_name' => $lead->full_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'city' => $lead->city,
            'state' => $lead->state,
            'usdot' => $lead->usdot,
            'carrier_class' => $lead->carrier_class,
            'truck_count' => $lead->truck_count,
            'trailer_count' => $lead->trailer_count,
            'insurance_status' => $lead->insurance_answer === 'yes' ? 'active' : 'inactive',
            'status' => 'pending_review',
            'notes' => 'Converted from lead #' . $lead->id,
        ]);

        $lead->update([
            'linked_carrier_id' => $carrier->id,
            'lead_status' => 'converted_to_carrier',
        ]);

        return response()->json([
            'ok' => true,
            'carrier' => $carrier,
            'lead' => $lead->fresh(),
        ]);
    }
}
