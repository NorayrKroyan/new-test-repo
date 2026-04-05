<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\BoldSignWebhookService;
use Illuminate\Http\Request;

class BoldSignWebhookController extends Controller
{
    public function __construct(
        protected BoldSignWebhookService $webhooks
    ) {
    }

    public function store(Request $request)
    {
        $event = $this->webhooks->handle(
            $request->getContent(),
            $request->header('X-BoldSign-Signature'),
            $request->all()
        );

        return response()->json([
            'ok' => true,
            'event_id' => $event->id,
        ]);
    }
}
