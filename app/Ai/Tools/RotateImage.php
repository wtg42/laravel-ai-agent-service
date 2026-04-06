<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Symfony\Component\Process\Process;

class RotateImage implements Tool
{
    public function description(): Stringable|string
    {
        return 'Rotate a local image file by 90, 180, or 270 degrees and return the new image path.';
    }

    public function handle(Request $request): Stringable|string
    {
        return json_encode(
            $this->transform(
                (string) $request['source_path'],
                (string) $request['run_id'],
                (int) $request['step'],
                ['degrees' => (int) $request['degrees']],
            ),
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'source_path' => $schema->string()->required(),
            'run_id' => $schema->string()->required(),
            'step' => $schema->integer()->min(1)->required(),
            'degrees' => $schema->integer()->required(),
        ];
    }

    /**
     * @param  array{degrees?: ?int}  $parameters
     * @return array{path: string, parameters: array{degrees: int}}
     */
    public function transform(string $sourcePath, string $runId, int $step, array $parameters): array
    {
        $degrees = (int) ($parameters['degrees'] ?? 90);

        if (! in_array($degrees, [90, 180, 270], true)) {
            $degrees = 90;
        }

        $outputPath = sprintf('adaptive-ocr/%s/step-%02d-rotate.png', $runId, $step);

        $this->runCommand([
            'magick',
            Storage::disk('local')->path($sourcePath),
            '-rotate',
            (string) $degrees,
            Storage::disk('local')->path($outputPath),
        ]);

        return [
            'path' => $outputPath,
            'parameters' => ['degrees' => $degrees],
        ];
    }

    /**
     * @param  list<string>  $command
     */
    private function runCommand(array $command): void
    {
        $process = new Process($command);
        $process->mustRun();
    }
}
