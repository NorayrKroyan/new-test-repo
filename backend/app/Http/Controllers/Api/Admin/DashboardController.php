<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\JobAvailable;
use App\Models\Lead;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        return response()->json([
            'cards' => [
                'admin_users' => User::admins()->count(),
                'leads_total' => Lead::count(),
                'leads_new' => Lead::where('lead_status', 'new')->count(),
                'carriers_total' => Carrier::count(),
                'jobs_open' => JobAvailable::where('status', 'open')->count(),
            ],
        ]);
    }
}
