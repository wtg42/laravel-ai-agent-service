<?php

use App\Ai\Agents\EmailScanAgent;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Tests\TestCase;

pest()->extend(TestCase::class);

it('describes the email scanning strategy in its instructions', function () {
    $agent = new EmailScanAgent;
    $instructions = (string) $agent->instructions();

    expect($instructions)->toContain('You scan email text')
        ->toContain('Return results in the names array only')
        ->toContain('Provide a short evidence snippet copied from the source email text');
});

it('keeps the names structured output schema contract for email scans', function () {
    $agent = new EmailScanAgent;
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
