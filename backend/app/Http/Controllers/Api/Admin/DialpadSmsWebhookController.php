<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\DialpadSmsWebhookService;
use Illuminate\Http\Request;

class DialpadSmsWebhookController extends Controller
{
    public function __construct(
        protected DialpadSmsWebhookService $dialpadSmsWebhooks
    ) {
    }

    public function store(Request $request)
    {
        $result = $this->dialpadSmsWebhooks->handle((string) $request->getContent());

        return response()->json([
            'ok' => true,
            'result' => $result,
        ]);
    }
}
