<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobAvailable;
use Illuminate\Http\Request;

class JobAvailableController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $rows = JobAvailable::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('job_number', 'like', "%{$q}%")
                        ->orWhere('title', 'like', "%{$q}%")
                        ->orWhere('origin_city', 'like', "%{$q}%")
                        ->orWhere('destination_city', 'like', "%{$q}%")
                        ->orWhere('equipment_type', 'like', "%{$q}%")
                        ->orWhere('status', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json($rows);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'job_number' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'origin_city' => ['nullable', 'string', 'max:255'],
            'origin_state' => ['nullable', 'string', 'max:255'],
            'destination_city' => ['nullable', 'string', 'max:255'],
            'destination_state' => ['nullable', 'string', 'max:255'],
            'equipment_type' => ['nullable', 'string', 'max:255'],
            'trailer_type' => ['nullable', 'string', 'max:255'],
            'weight' => ['nullable', 'numeric'],
            'rate' => ['nullable', 'numeric'],
            'status' => ['nullable', 'string', 'max:255'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_company' => ['nullable', 'string', 'max:255'],
            'posted_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $data['created_by_admin_id'] = $request->user()->id;

        $row = JobAvailable::create($data);

        return response()->json($row, 201);
    }

    public function show(JobAvailable $jobs_available)
    {
        return response()->json($jobs_available);
    }

    public function update(Request $request, JobAvailable $jobs_available)
    {
        $data = $request->validate([
            'job_number' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'origin_city' => ['nullable', 'string', 'max:255'],
            'origin_state' => ['nullable', 'string', 'max:255'],
            'destination_city' => ['nullable', 'string', 'max:255'],
            'destination_state' => ['nullable', 'string', 'max:255'],
            'equipment_type' => ['nullable', 'string', 'max:255'],
            'trailer_type' => ['nullable', 'string', 'max:255'],
            'weight' => ['nullable', 'numeric'],
            'rate' => ['nullable', 'numeric'],
            'status' => ['nullable', 'string', 'max:255'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_company' => ['nullable', 'string', 'max:255'],
            'posted_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $jobs_available->update($data);

        return response()->json($jobs_available);
    }

    public function destroy(JobAvailable $jobs_available)
    {
        $jobs_available->delete();

        return response()->json([
            'ok' => true,
        ]);
    }
}
