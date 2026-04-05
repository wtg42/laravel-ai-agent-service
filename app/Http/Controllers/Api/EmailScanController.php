<?php

namespace App\Http\Controllers\Api;

use App\Ai\Agents\EmailScanAgent;
use App\Ai\Tools\NormalizeDetectedNames;
use App\Http\Controllers\Controller;
use App\Http\Requests\ScanEmailRequest;
use Illuminate\Http\JsonResponse;
use Throwable;

class EmailScanController extends Controller
{
    public function __invoke(
        ScanEmailRequest $request,
        EmailScanAgent $agent,
        NormalizeDetectedNames $normalizer,
    ): JsonResponse {
        try {
            $response = $agent->prompt($request->validated('content'));
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Email scan is currently unavailable.',
            ], 503);
        }

        return response()->json([
            'names' => $normalizer->normalize($response['names'] ?? []),
        ]);
    }
}
