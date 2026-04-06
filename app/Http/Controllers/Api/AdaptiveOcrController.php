<?php

namespace App\Http\Controllers\Api;

use App\Ai\AdaptiveOcrWorkflow;
use App\Http\Controllers\Controller;
use App\Http\Requests\ScanImageRequest;
use Illuminate\Http\JsonResponse;
use Throwable;

class AdaptiveOcrController extends Controller
{
    public function __invoke(ScanImageRequest $request, AdaptiveOcrWorkflow $workflow): JsonResponse
    {
        try {
            $result = $workflow->run($request->file('image'));
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Adaptive OCR is currently unavailable.',
            ], 503);
        }

        return response()->json($result);
    }
}
