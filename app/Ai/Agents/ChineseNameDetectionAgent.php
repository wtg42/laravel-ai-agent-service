<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

class ChineseNameDetectionAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
You detect explicit Chinese personal full names from Traditional Chinese text.

Rules:
- Return only names that are clearly identifiable as Chinese personal full names.
- Prefer precision over recall. If uncertain, omit the candidate.
- Exclude title-only references, nicknames, masked names, departments, organizations, and locations.
- Normalize each detected name to the clean full name only.
- Provide a short evidence snippet copied from the source text.
- Set confidence to a number between 0 and 1.
- Return an empty names array when no explicit Chinese full names are found.
PROMPT;
    }

    public function provider(): Lab|string
    {
        return Lab::Ollama;
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
            'names' => $schema->array()
                ->items($schema->object([
                    'value' => $schema->string()->required(),
                    'evidence' => $schema->string()->required(),
                    'confidence' => $schema->number()->min(0)->max(1)->required(),
                ]))
                ->required(),
        ];
    }
}
