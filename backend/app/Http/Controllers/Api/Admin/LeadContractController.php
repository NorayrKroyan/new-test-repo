<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Services\LeadContractService;
use Illuminate\Http\Request;

class LeadContractController extends Controller
{
    public function __construct(
        protected LeadContractService $contracts
    ) {
    }

    public function index(Lead $lead)
    {
        return response()->json([
            'data' => $this->contracts->historyForLead($lead),
            'meta' => [
                'lead_id' => $lead->id,
            ],
        ]);
    }

    public function templateOptions()
    {
        return response()->json([
            'data' => $this->contracts->templateOptions(),
        ]);
    }

    public function send(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'source_type' => ['required', 'in:template,upload'],
            'template_key' => ['nullable', 'string', 'max:255'],
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
}
