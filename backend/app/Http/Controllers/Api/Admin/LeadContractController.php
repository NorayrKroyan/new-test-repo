<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Services\LeadContractService;
use Illuminate\Http\Request;

class LeadContractController extends Controller
{
    public function __construct(
        protected LeadContractService $contracts,
    ) {
    }

    public function index(Lead $lead)
    {
        return response()->json([
            'data' => $this->contracts->historyForLead($lead, $requestUser = request()->user()),
            'meta' => [
                'lead_id' => $lead->id,
            ],
        ]);
    }

    public function templateOptions(Request $request)
    {
        $lead = null;
        $leadId = (int) $request->integer('lead_id');

        if ($leadId > 0) {
            $lead = Lead::query()->find($leadId);
        }

        $jobAvailableId = $request->filled('job_available_id') ? (int) $request->input('job_available_id') : null;

        return response()->json([
            'data' => $this->contracts->templateOptions($lead, $jobAvailableId),
            'meta' => [
                'lead_id' => $lead?->id,
                'job_available_id' => $jobAvailableId ?: ($lead->job_available_id ?? null),
            ],
        ]);
    }

    public function send(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'source_type' => ['required', 'in:template,upload'],
            'template_key' => ['nullable', 'string', 'max:255'],
            'template_id' => ['nullable', 'string', 'max:255'],
            'job_available_id' => ['nullable', 'integer', 'min:1'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'recipient_email' => ['required', 'email', 'max:255'],
            'document_name' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx'],
        ]);

        $contract = $this->contracts->sendForLead(
            $lead,
            $data,
            $request->file('file'),
            optional($request->user())->id
        );

        return response()->json([
            'ok' => true,
            'data' => $contract,
        ]);
    }

    public function documentUrl(Lead $lead, int $contract)
    {
        return response()->json([
            'data' => $this->contracts->managementDocumentUrlForLead($lead, $contract, request()->user()),
            'meta' => [
                'lead_id' => $lead->id,
                'contract_id' => $contract,
            ],
        ]);
    }
}
