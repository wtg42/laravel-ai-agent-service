<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Enums\Lab;
use Stringable;

#[Provider(Lab::Ollama)]
class EmailScanAgent extends ChineseNameDetectionAgent
{
    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
You scan email text and return explicit Chinese personal full names that are clearly mentioned in the content.

Rules:
- Return results in the names array only.
- Return only names that are clearly identifiable as Chinese personal full names.
- Prefer precision over recall. If uncertain, omit the candidate.
- Exclude title-only references, nicknames, masked names, departments, organizations, and locations.
- Normalize each detected name to the clean full name only.
- Provide a short evidence snippet copied from the source email text.
- Set confidence to a number between 0 and 1.
- Return an empty names array when no explicit Chinese full names are found.
PROMPT;
    }
}
