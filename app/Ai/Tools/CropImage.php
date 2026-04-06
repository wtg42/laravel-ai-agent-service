<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Symfony\Component\Process\Process;

class CropImage implements Tool
{
    public function description(): Stringable|string
    {
        return 'Crop a local image file to a requested rectangle and return the new image path.';
    }

    public function handle(Request $request): Stringable|string
    {
        return json_encode(
            $this->transform(
                (string) $request['source_path'],
                (string) $request['run_id'],
                (int) $request['step'],
                [
                    'x' => (int) $request['x'],
                    'y' => (int) $request['y'],
                    'width' => (int) $request['width'],
                    'height' => (int) $request['height'],
                ],
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
            'x' => $schema->integer()->required(),
            'y' => $schema->integer()->required(),
            'width' => $schema->integer()->required(),
            'height' => $schema->integer()->required(),
        ];
    }

    /**
     * @param  array{x?: ?int, y?: ?int, width?: ?int, height?: ?int}  $parameters
     * @return array{path: string, parameters: array{x: int, y: int, width: int, height: int}}
     */
    public function transform(string $sourcePath, string $runId, int $step, array $parameters): array
    {
        $x = max(0, (int) ($parameters['x'] ?? 0));
        $y = max(0, (int) ($parameters['y'] ?? 0));
        $width = max(1, (int) ($parameters['width'] ?? 1));
        $height = max(1, (int) ($parameters['height'] ?? 1));

        $outputPath = sprintf('adaptive-ocr/%s/step-%02d-crop.png', $runId, $step);

        $this->runCommand([
            'magick',
            Storage::disk('local')->path($sourcePath),
            '-crop',
            sprintf('%dx%d+%d+%d', $width, $height, $x, $y),
            '+repage',
            Storage::disk('local')->path($outputPath),
        ]);

        return [
            'path' => $outputPath,
            'parameters' => [
                'x' => $x,
                'y' => $y,
                'width' => $width,
                'height' => $height,
            ],
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
