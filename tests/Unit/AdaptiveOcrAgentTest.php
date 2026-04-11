<?php

use App\Ai\Agents\AdaptiveOcrAgent;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Tests\TestCase;

pest()->extend(TestCase::class);

it('uses the configured model and timeout for adaptive ocr', function () {
    config()->set('services.ollama.model', 'gemma4:vision-test');
    config()->set('services.ollama.timeout', 75);

    $agent = new AdaptiveOcrAgent;

    expect($agent->model())->toBe('gemma4:vision-test')
        ->and($agent->timeout())->toBe(75);
});

it('describes the adaptive ocr decision strategy in its instructions', function () {
    $instructions = (string) (new AdaptiveOcrAgent)->instructions();

    expect($instructions)->toContain('decision center for adaptive OCR')
        ->toContain('Allowed actions:')
        ->toContain('analyze_now')
        ->toContain('reject_image');
});

it('defines the expected structured output contract for adaptive ocr', function () {
    $schema = (new AdaptiveOcrAgent)->schema(new JsonSchemaTypeFactory);

    expect(array_keys($schema))->toBe([
        'action',
        'reason',
        'confidence',
        'text',
        'warnings',
        'quality_issues',
        'parameters',
    ])->and($schema['action']->toArray())->toBe(['type' => 'string'])
        ->and($schema['confidence']->toArray())->toBe([
            'minimum' => 0,
            'maximum' => 1,
            'type' => 'number',
        ])
        ->and($schema['warnings']->toArray())->toBe([
            'items' => ['type' => 'string'],
            'type' => 'array',
        ]);

    expect($schema['parameters']->toArray()['properties'])->toHaveKeys([
        'degrees',
        'x',
        'y',
        'width',
        'height',
    ]);
});
