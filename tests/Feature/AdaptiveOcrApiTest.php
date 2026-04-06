<?php

use App\Ai\AdaptiveOcrWorkflow;
use App\Ai\Agents\AdaptiveOcrAgent;
use App\Ai\Agents\EmailScanAgent;
use Illuminate\Testing\Fluent\AssertableJson;

it('starts adaptive ocr with a valid image upload', function () {
    AdaptiveOcrAgent::fake([
        [
            'action' => 'analyze_now',
            'reason' => 'The image text is already readable.',
            'confidence' => 0.96,
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

    $response = $this->postJson('/api/pii/adaptive-ocr', [
        'image' => testUploadedImage(),
    ]);

    $response
        ->assertSuccessful()
        ->assertJson(fn (AssertableJson $json) => $json->where('status', 'completed')
            ->where('text', '王小明')
            ->where('meta.stop_reason', 'analyze_now')
            ->where('meta.attempts', 1)
            ->has('meta.steps', 0)
            ->etc()
        );

    AdaptiveOcrAgent::assertPrompted(fn ($prompt) => $prompt->attachments->count() === 1);
});

it('rejects requests without a valid image', function () {
    AdaptiveOcrAgent::fake()->preventStrayPrompts();

    $response = $this->postJson('/api/pii/adaptive-ocr', []);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['image']);

    AdaptiveOcrAgent::assertNeverPrompted();
});

it('returns an unprocessable adaptive ocr result when the agent rejects the image', function () {
    AdaptiveOcrAgent::fake([
        [
            'action' => 'reject_image',
            'reason' => 'The image is too blurry to recover.',
            'confidence' => 0.9,
            'text' => null,
            'warnings' => ['Image is too blurry to recover safely.'],
            'quality_issues' => ['blur'],
            'parameters' => [
                'degrees' => null,
                'x' => null,
                'y' => null,
                'width' => null,
                'height' => null,
            ],
        ],
    ])->preventStrayPrompts();

    $response = $this->postJson('/api/pii/adaptive-ocr', [
        'image' => testUploadedImage(),
    ]);

    $response
        ->assertSuccessful()
        ->assertJson(fn (AssertableJson $json) => $json->where('status', 'unprocessable')
            ->where('text', '')
            ->where('meta.stop_reason', 'reject_image')
            ->where('meta.quality_issues.0', 'blur')
            ->etc()
        );
});

it('returns service unavailable when adaptive ocr fails unexpectedly', function () {
    AdaptiveOcrAgent::fake(function () {
        throw new RuntimeException('Ollama is offline');
    })->preventStrayPrompts();

    $response = $this->postJson('/api/pii/adaptive-ocr', [
        'image' => testUploadedImage(),
    ]);

    $response
        ->assertServiceUnavailable()
        ->assertJson(fn (AssertableJson $json) => $json->where('message', 'Adaptive OCR is currently unavailable.')
            ->etc()
        );
});

it('raises the request execution limit for adaptive ocr based on the configured timeout', function () {
    config()->set('services.ollama.timeout', 180);

    $workflow = Mockery::mock(AdaptiveOcrWorkflow::class);
    $workflow->shouldReceive('run')->once()->andReturnUsing(fn () => [
        'status' => 'completed',
        'text' => '王小明',
        'warnings' => [],
        'meta' => [
            'execution_limit' => (int) ini_get('max_execution_time'),
        ],
    ]);

    app()->instance(AdaptiveOcrWorkflow::class, $workflow);

    $response = $this->postJson('/api/pii/adaptive-ocr', [
        'image' => testUploadedImage(),
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('meta.execution_limit', 185);
});

it('does not change the request execution limit for other pii routes', function () {
    $originalExecutionLimit = (int) ini_get('max_execution_time');

    EmailScanAgent::fake(function () use ($originalExecutionLimit) {
        expect((int) ini_get('max_execution_time'))->toBe($originalExecutionLimit);

        return ['names' => []];
    })->preventStrayPrompts();

    $response = $this->postJson('/api/pii/email-scan', [
        'content' => '您好，我是王小明。',
    ]);

    $response
        ->assertSuccessful()
        ->assertJson(fn (AssertableJson $json) => $json->has('names', 0)->etc());
});
