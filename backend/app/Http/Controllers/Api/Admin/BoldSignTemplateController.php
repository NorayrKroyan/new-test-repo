<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BoldSignTemplate;
use App\Models\BoldSignTemplateOverride;
use App\Services\BoldSignTemplateSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BoldSignTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = BoldSignTemplate::query()
            ->with('override')
            ->orderByRaw('CASE WHEN is_active = 1 THEN 0 ELSE 1 END')
            ->orderBy('template_name');

        if ($request->boolean('active_only', true)) {
            $query->where('is_active', true);
        }

        return response()->json([
            'data' => $query->get(),
        ]);
    }

    public function sync(Request $request, BoldSignTemplateSyncService $syncService): JsonResponse
    {
        $result = $syncService->syncAll(
            syncDetails: !$request->boolean('skip_details', false)
        );

        return response()->json([
            'message' => 'BoldSign templates synchronized successfully.',
            'data' => $result,
        ]);
    }

    public function show(string $templateId): JsonResponse
    {
        $template = BoldSignTemplate::query()
            ->with('override')
            ->where('template_id', $templateId)
            ->firstOrFail();

        return response()->json([
            'data' => $template,
        ]);
    }

    public function saveOverride(Request $request, string $templateId): JsonResponse
    {
        $template = BoldSignTemplate::query()
            ->where('template_id', $templateId)
            ->firstOrFail();

        $data = $request->validate([
            'preferred_signer_role' => ['nullable', 'string', 'max:255'],
            'preferred_signer_role_index' => ['nullable', 'integer', 'min:1'],
            'field_map_json' => ['nullable', 'array'],
            'field_map_json.*' => ['nullable'],
            'is_enabled' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $override = BoldSignTemplateOverride::query()->updateOrCreate(
            ['template_id' => $template->template_id],
            [
                'preferred_signer_role' => $data['preferred_signer_role'] ?? null,
                'preferred_signer_role_index' => $data['preferred_signer_role_index'] ?? null,
                'field_map_json' => $data['field_map_json'] ?? null,
                'is_enabled' => array_key_exists('is_enabled', $data) ? (bool) $data['is_enabled'] : true,
                'notes' => $data['notes'] ?? null,
            ]
        );

        return response()->json([
            'message' => 'BoldSign template override saved successfully.',
            'data' => $override->fresh(),
        ]);
    }
}
