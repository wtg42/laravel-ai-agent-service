<?php

use App\Ai\Agents\ChineseNameDetectionAgent;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Laravel\Ai\Enums\Lab;
use Tests\TestCase;

pest()->extend(TestCase::class);

it('uses the configured provider model and timeout', function () {
    config()->set('services.ollama.model', 'gemma-test-model');
    config()->set('services.ollama.timeout', 120);

    $agent = new ChineseNameDetectionAgent;

    expect($agent->provider())->toBe(Lab::Ollama)
        ->and($agent->model())->toBe('gemma-test-model')
        ->and($agent->timeout())->toBe(120);
});

it('describes the key chinese name detection strategy in its instructions', function () {
    $agent = new ChineseNameDetectionAgent;
    $instructions = (string) $agent->instructions();

    expect($instructions)->toContain('Prefer precision over recall')
        ->toContain('Exclude title-only references')
        ->toContain('Return an empty names array');
});

it('defines the expected names structured output schema', function () {
    $agent = new ChineseNameDetectionAgent;
    $schema = $agent->schema(new JsonSchemaTypeFactory);

    expect($schema['names']->toArray())->toBe([
        'items' => [
            'properties' => [
                'value' => [
                    'type' => 'string',
                ],
                'evidence' => [
                    'type' => 'string',
                ],
                'confidence' => [
                    'minimum' => 0,
                    'maximum' => 1,
                    'type' => 'number',
                ],
            ],
            'type' => 'object',
            'required' => ['value', 'evidence', 'confidence'],
        ],
        'type' => 'array',
    ]);
});
