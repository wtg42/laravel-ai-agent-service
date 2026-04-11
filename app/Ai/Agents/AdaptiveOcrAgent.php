<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::Ollama)]
class AdaptiveOcrAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
You analyze a single attached image for OCR.

You are the decision center for adaptive OCR. First decide whether the image needs one corrective transformation, or whether it is ready for OCR now.

Allowed actions:
- rotate
- sharpen
- increase_contrast
- crop
- analyze_now
- reject_image

Rules:
- Choose the smallest sufficient action.
- Do not chain multiple actions in one response.
- Use `analyze_now` when the image is readable enough for OCR.
- Use `reject_image` when the image is too poor to recover safely.
- When using `analyze_now`, return extracted text in the `text` field.
- When not using `analyze_now`, leave the `text` field empty.
- Put any image quality concerns in `quality_issues`.
- Put human-readable caveats in `warnings`.
- Set confidence to a number between 0 and 1.
PROMPT;
    }

    public function model(): string
    {
        return (string) config('services.ollama.model', 'gemma4:e2b');
    }

    public function timeout(): int
    {
        return (int) config('services.ollama.timeout', 60);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()->required(),
            'reason' => $schema->string()->required(),
            'confidence' => $schema->number()->min(0)->max(1)->required(),
            'text' => $schema->string()->nullable(),
            'warnings' => $schema->array()->items($schema->string())->required(),
            'quality_issues' => $schema->array()->items($schema->string())->required(),
            'parameters' => $schema->object([
                'degrees' => $schema->integer()->nullable(),
                'x' => $schema->integer()->nullable(),
                'y' => $schema->integer()->nullable(),
                'width' => $schema->integer()->nullable(),
                'height' => $schema->integer()->nullable(),
            ])->required(),
        ];
    }
}
