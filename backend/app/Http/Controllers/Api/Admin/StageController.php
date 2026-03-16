<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Stage;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StageController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $group = trim((string) $request->query('group', ''));

        $rows = Stage::query()
            ->when($group !== '', function ($query) use ($group) {
                $query->where('stage_group', $group);
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('stage_name', 'like', "%{$q}%")
                        ->orWhere('stage_group', 'like', "%{$q}%")
                        ->orWhere('stage_order', 'like', "%{$q}%");
                });
            })
            ->orderBy('stage_group')
            ->orderBy('stage_order')
            ->orderBy('stage_name')
            ->get();

        $groups = Lead::query()
            ->whereNotNull('ad_name')
            ->where('ad_name', '!=', '')
            ->distinct()
            ->orderBy('ad_name')
            ->pluck('ad_name')
            ->values();

        return response()->json([
            'data' => $rows,
            'meta' => [
                'groups' => $groups,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $row = Stage::create($this->validateStage($request));

        return response()->json($row, 201);
    }

    public function show(Stage $stage)
    {
        return response()->json($stage);
    }

    public function update(Request $request, Stage $stage)
    {
        $stage->update($this->validateStage($request, $stage));

        return response()->json($stage->fresh());
    }

    public function destroy(Stage $stage)
    {
        if ($stage->leads()->exists()) {
            throw ValidationException::withMessages([
                'stage' => 'This stage is already assigned to one or more leads and cannot be deleted.',
            ]);
        }

        $stage->delete();

        return response()->json([
            'ok' => true,
        ]);
    }

    private function validateStage(Request $request, ?Stage $stage = null): array
    {
        return $request->validate([
            'stage_name' => ['required', 'string', 'max:120'],
            'stage_group' => ['required', 'string', 'max:120'],
            'stage_order' => ['required', 'integer', 'min:1', 'max:999'],
        ]);
    }
}
