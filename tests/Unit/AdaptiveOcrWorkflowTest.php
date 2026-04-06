<?php

use App\Ai\AdaptiveOcrWorkflow;
use App\Ai\Agents\AdaptiveOcrAgent;
use App\Ai\Tools\CropImage;
use App\Ai\Tools\IncreaseContrastImage;
use App\Ai\Tools\RotateImage;
use App\Ai\Tools\SharpenImage;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

pest()->extend(TestCase::class);

it('executes a tool step before completing adaptive ocr', function () {
    AdaptiveOcrAgent::fake([
        [
            'action' => 'rotate',
            'reason' => 'The text appears rotated sideways.',
            'confidence' => 0.88,
            'text' => null,
            'warnings' => [],
            'quality_issues' => ['rotation'],
            'parameters' => [
                'degrees' => 90,
                'x' => null,
                'y' => null,
                'width' => null,
                'height' => null,
            ],
        ],
        [
            'action' => 'analyze_now',
            'reason' => 'The corrected image is readable enough for OCR.',
            'confidence' => 0.94,
            'text' => '王小明',
            'warnings' => [],
            'quality_issues' => [],
            'parameters' => [
                'degrees' => null,
                'x' => null,
                'y' => null,
                'width' => null,
                'height' => null,
            ],
        ],
    ])->preventStrayPrompts();

    $workflow = new AdaptiveOcrWorkflow(
        new AdaptiveOcrAgent,
        new RotateImage,
        new SharpenImage,
        new IncreaseContrastImage,
        new CropImage,
    );

    $result = $workflow->run(testUploadedImage());

    expect($result['status'])->toBe('completed')
        ->and($result['text'])->toBe('王小明')
        ->and($result['meta']['attempts'])->toBe(2)
        ->and($result['meta']['stop_reason'])->toBe('analyze_now')
        ->and($result['meta']['steps'])->toHaveCount(1)
        ->and($result['meta']['steps'][0]['action'])->toBe('rotate');

    expect(Storage::disk('local')->exists($result['meta']['steps'][0]['output_path']))->toBeTrue();
});

it('stops adaptive ocr when the same tool is requested repeatedly', function () {
    AdaptiveOcrAgent::fake([
        [
            'action' => 'rotate',
            'reason' => 'The image is sideways.',
            'confidence' => 0.83,
            'text' => null,
            'warnings' => [],
            'quality_issues' => ['rotation'],
            'parameters' => [
                'degrees' => 90,
                'x' => null,
                'y' => null,
                'width' => null,
                'height' => null,
            ],
        ],
        [
            'action' => 'rotate',
            'reason' => 'Rotate again.',
            'confidence' => 0.61,
            'text' => null,
            'warnings' => [],
            'quality_issues' => ['rotation'],
            'parameters' => [
                'degrees' => 180,
                'x' => null,
                'y' => null,
                'width' => null,
                'height' => null,
            ],
        ],
    ])->preventStrayPrompts();

    $workflow = new AdaptiveOcrWorkflow(
        new AdaptiveOcrAgent,
        new RotateImage,
        new SharpenImage,
        new IncreaseContrastImage,
        new CropImage,
    );

    $result = $workflow->run(testUploadedImage());

    expect($result['status'])->toBe('unprocessable')
        ->and($result['meta']['stop_reason'])->toBe('repeated_action_blocked')
        ->and($result['meta']['steps'])->toHaveCount(1);
});

it('stops adaptive ocr after the maximum number of tool steps', function () {
    AdaptiveOcrAgent::fake([
        [
            'action' => 'rotate',
            'reason' => 'Rotate first.',
            'confidence' => 0.84,
            'text' => null,
            'warnings' => [],
            'quality_issues' => ['rotation'],
            'parameters' => ['degrees' => 90, 'x' => null, 'y' => null, 'width' => null, 'height' => null],
        ],
        [
            'action' => 'sharpen',
            'reason' => 'Sharpen next.',
            'confidence' => 0.72,
            'text' => null,
            'warnings' => [],
            'quality_issues' => ['blur'],
            'parameters' => ['degrees' => null, 'x' => null, 'y' => null, 'width' => null, 'height' => null],
        ],
        [
            'action' => 'increase_contrast',
            'reason' => 'Contrast is still low.',
            'confidence' => 0.7,
            'text' => null,
            'warnings' => [],
            'quality_issues' => ['low_contrast'],
            'parameters' => ['degrees' => null, 'x' => null, 'y' => null, 'width' => null, 'height' => null],
        ],
        [
            'action' => 'crop',
            'reason' => 'Crop one more time.',
            'confidence' => 0.65,
            'text' => null,
            'warnings' => [],
            'quality_issues' => ['extra_border'],
            'parameters' => ['degrees' => null, 'x' => 0, 'y' => 0, 'width' => 1, 'height' => 1],
        ],
    ])->preventStrayPrompts();

    $workflow = new AdaptiveOcrWorkflow(
        new AdaptiveOcrAgent,
        new RotateImage,
        new SharpenImage,
        new IncreaseContrastImage,
        new CropImage,
    );

    $result = $workflow->run(testUploadedImage());

    expect($result['status'])->toBe('unprocessable')
        ->and($result['meta']['stop_reason'])->toBe('max_steps_reached')
        ->and($result['meta']['steps'])->toHaveCount(3);
});

it('returns an unprocessable result when the agent rejects the image', function () {
    AdaptiveOcrAgent::fake([
        [
            'action' => 'reject_image',
            'reason' => 'The image is too blurred to recover.',
            'confidence' => 0.93,
            'text' => null,
            'warnings' => ['Please re-upload a clearer image.'],
            'quality_issues' => ['blur'],
            'parameters' => ['degrees' => null, 'x' => null, 'y' => null, 'width' => null, 'height' => null],
        ],
    ])->preventStrayPrompts();

    $workflow = new AdaptiveOcrWorkflow(
        new AdaptiveOcrAgent,
        new RotateImage,
        new SharpenImage,
        new IncreaseContrastImage,
        new CropImage,
    );

    $result = $workflow->run(testUploadedImage());

    expect($result['status'])->toBe('unprocessable')
        ->and($result['warnings'])->toContain('Please re-upload a clearer image.')
        ->and($result['meta']['stop_reason'])->toBe('reject_image');
});
