<?php

namespace App\Http\Controllers\Api;

use App\Ai\Agents\ChineseNameDetectionAgent;
use App\Ai\Tools\NormalizeDetectedNames;
use App\Http\Controllers\Controller;
use App\Http\Requests\DetectChineseNamesRequest;
use Illuminate\Http\JsonResponse;
use Throwable;

class ChineseNameDetectionController extends Controller
{
    public function store(
        DetectChineseNamesRequest $request,
        ChineseNameDetectionAgent $agent,
        NormalizeDetectedNames $normalizer,
    ): JsonResponse {
        try {
            $response = $agent->prompt($request->validated('content'));
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Chinese name detection is currently unavailable.',
            ], 503);
        }

        return response()->json([
            'names' => $normalizer->normalize($response['names'] ?? []),
        ]);
    }
}
