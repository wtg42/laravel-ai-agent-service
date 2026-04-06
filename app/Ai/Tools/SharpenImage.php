<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Symfony\Component\Process\Process;

class SharpenImage implements Tool
{
    public function description(): Stringable|string
    {
        return 'Sharpen a local image file and return the new image path.';
    }

    public function handle(Request $request): Stringable|string
    {
        return json_encode(
            $this->transform(
                (string) $request['source_path'],
                (string) $request['run_id'],
                (int) $request['step'],
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
        ];
    }

    /**
     * @return array{path: string, parameters: array<string, int>}
     */
    public function transform(string $sourcePath, string $runId, int $step): array
    {
        $outputPath = sprintf('adaptive-ocr/%s/step-%02d-sharpen.png', $runId, $step);

        $this->runCommand([
            'magick',
            Storage::disk('local')->path($sourcePath),
            '-sharpen',
            '0x1',
            Storage::disk('local')->path($outputPath),
        ]);

        return [
            'path' => $outputPath,
            'parameters' => [],
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
