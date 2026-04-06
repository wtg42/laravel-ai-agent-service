<?php

namespace App\Ai;

use App\Ai\Agents\AdaptiveOcrAgent;
use App\Ai\Tools\CropImage;
use App\Ai\Tools\IncreaseContrastImage;
use App\Ai\Tools\RotateImage;
use App\Ai\Tools\SharpenImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Ai\Files;

class AdaptiveOcrWorkflow
{
    private const int MAX_TOOL_STEPS = 3;

    public function __construct(
        private AdaptiveOcrAgent $agent,
        private RotateImage $rotateImage,
        private SharpenImage $sharpenImage,
        private IncreaseContrastImage $increaseContrastImage,
        private CropImage $cropImage,
    ) {}

    /**
     * @return array{
     *     status: 'completed'|'unprocessable',
     *     text: string,
     *     warnings: list<string>,
     *     meta: array{
     *         run_id: string,
     *         stop_reason: string,
     *         attempts: int,
     *         steps: list<array<string, mixed>>,
     *         final_image_path: string,
     *         quality_issues: list<string>
     *     }
     * }
     */
    public function run(UploadedFile $image): array
    {
        $runId = (string) Str::uuid();
        $currentPath = $this->storeOriginalImage($image, $runId);
        $steps = [];
        $attempts = 0;
        $lastAction = null;

        while (true) {
            $attempts++;

            /** @var array<string, mixed> $rawResponse */
            $rawResponse = json_decode(
                (string) $this->agent->prompt(
                    $this->buildPrompt($steps),
                    attachments: [$this->imageAttachment($currentPath)],
                ),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );

            $decision = $this->normalizeDecision($rawResponse);

            if ($decision['action'] === 'analyze_now') {
                $text = trim($decision['text']);

                if ($text === '') {
                    return $this->result(
                        status: 'unprocessable',
                        text: '',
                        warnings: array_values(array_unique([...$decision['warnings'], 'The model selected OCR analysis but did not return any text.'])),
                        runId: $runId,
                        stopReason: 'missing_text',
                        attempts: $attempts,
                        steps: $steps,
                        finalImagePath: $currentPath,
                        qualityIssues: $decision['quality_issues'],
                    );
                }

                return $this->result(
                    status: 'completed',
                    text: $text,
                    warnings: $decision['warnings'],
                    runId: $runId,
                    stopReason: 'analyze_now',
                    attempts: $attempts,
                    steps: $steps,
                    finalImagePath: $currentPath,
                    qualityIssues: $decision['quality_issues'],
                );
            }

            if ($decision['action'] === 'reject_image') {
                return $this->result(
                    status: 'unprocessable',
                    text: '',
                    warnings: $decision['warnings'],
                    runId: $runId,
                    stopReason: 'reject_image',
                    attempts: $attempts,
                    steps: $steps,
                    finalImagePath: $currentPath,
                    qualityIssues: $decision['quality_issues'],
                );
            }

            if (count($steps) >= self::MAX_TOOL_STEPS) {
                return $this->result(
                    status: 'unprocessable',
                    text: '',
                    warnings: ['Adaptive OCR reached the maximum number of image processing steps.'],
                    runId: $runId,
                    stopReason: 'max_steps_reached',
                    attempts: $attempts,
                    steps: $steps,
                    finalImagePath: $currentPath,
                    qualityIssues: $decision['quality_issues'],
                );
            }

            if ($lastAction !== null && $lastAction === $decision['action']) {
                return $this->result(
                    status: 'unprocessable',
                    text: '',
                    warnings: ['Adaptive OCR stopped because the same image tool was requested repeatedly.'],
                    runId: $runId,
                    stopReason: 'repeated_action_blocked',
                    attempts: $attempts,
                    steps: $steps,
                    finalImagePath: $currentPath,
                    qualityIssues: $decision['quality_issues'],
                );
            }

            $toolResult = $this->applyTool(
                $decision['action'],
                $currentPath,
                $runId,
                count($steps) + 1,
                $decision['parameters'],
            );

            $steps[] = [
                'action' => $decision['action'],
                'reason' => $decision['reason'],
                'confidence' => $decision['confidence'],
                'parameters' => $toolResult['parameters'],
                'input_path' => $currentPath,
                'output_path' => $toolResult['path'],
            ];

            $currentPath = $toolResult['path'];
            $lastAction = $decision['action'];
        }
    }

    /**
     * @param  list<array<string, mixed>>  $steps
     */
    private function buildPrompt(array $steps): string
    {
        if ($steps === []) {
            return <<<'PROMPT'
Review the attached image.

Decide whether it needs exactly one corrective action or whether it is ready for OCR now.
If the image is readable enough, use `analyze_now` and return all visible text in `text`.
If the image is too poor to recover safely, use `reject_image`.
PROMPT;
        }

        return sprintf(
            "Review the newly attached image after prior corrections.\n\nPrevious steps:\n%s\n\nChoose the next best single action. If the image is now readable enough, use `analyze_now` and return all visible text in `text`. If it cannot be recovered safely, use `reject_image`.",
            json_encode($steps, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
        );
    }

    private function imageAttachment(string $relativePath): object
    {
        return Files\Image::fromPath(Storage::disk('local')->path($relativePath));
    }

    private function storeOriginalImage(UploadedFile $image, string $runId): string
    {
        $extension = strtolower($image->getClientOriginalExtension() ?: $image->extension() ?: 'png');
        $path = sprintf('adaptive-ocr/%s/original.%s', $runId, $extension);

        Storage::disk('local')->putFileAs(
            dirname($path),
            $image,
            basename($path),
        );

        return $path;
    }

    /**
     * @param  array{degrees: ?int, x: ?int, y: ?int, width: ?int, height: ?int}  $parameters
     * @return array{path: string, parameters: array<string, int>}
     */
    private function applyTool(string $action, string $currentPath, string $runId, int $step, array $parameters): array
    {
        return match ($action) {
            'rotate' => $this->rotateImage->transform($currentPath, $runId, $step, $parameters),
            'sharpen' => $this->sharpenImage->transform($currentPath, $runId, $step),
            'increase_contrast' => $this->increaseContrastImage->transform($currentPath, $runId, $step),
            'crop' => $this->cropImage->transform($currentPath, $runId, $step, $parameters),
            default => throw new \InvalidArgumentException(sprintf('Unsupported OCR action [%s].', $action)),
        };
    }

    /**
     * @param  array<string, mixed>|\ArrayAccess<string, mixed>  $response
     * @return array{
     *     action: string,
     *     reason: string,
     *     confidence: float,
     *     text: string,
     *     warnings: list<string>,
     *     quality_issues: list<string>,
     *     parameters: array{degrees: ?int, x: ?int, y: ?int, width: ?int, height: ?int}
     * }
     */
    private function normalizeDecision(array|\ArrayAccess $response): array
    {
        $parameters = Arr::wrap($response['parameters'] ?? []);

        return [
            'action' => (string) ($response['action'] ?? 'reject_image'),
            'reason' => (string) ($response['reason'] ?? ''),
            'confidence' => max(0.0, min(1.0, (float) ($response['confidence'] ?? 0.0))),
            'text' => trim((string) ($response['text'] ?? '')),
            'warnings' => array_values(array_filter(array_map(static fn (mixed $warning): string => trim((string) $warning), Arr::wrap($response['warnings'] ?? [])))),
            'quality_issues' => array_values(array_filter(array_map(static fn (mixed $issue): string => trim((string) $issue), Arr::wrap($response['quality_issues'] ?? [])))),
            'parameters' => [
                'degrees' => $this->nullableInteger($parameters['degrees'] ?? null),
                'x' => $this->nullableInteger($parameters['x'] ?? null),
                'y' => $this->nullableInteger($parameters['y'] ?? null),
                'width' => $this->nullableInteger($parameters['width'] ?? null),
                'height' => $this->nullableInteger($parameters['height'] ?? null),
            ],
        ];
    }

    private function nullableInteger(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    /**
     * @param  list<string>  $warnings
     * @param  list<array<string, mixed>>  $steps
     * @param  list<string>  $qualityIssues
     * @return array{
     *     status: 'completed'|'unprocessable',
     *     text: string,
     *     warnings: list<string>,
     *     meta: array{
     *         run_id: string,
     *         stop_reason: string,
     *         attempts: int,
     *         steps: list<array<string, mixed>>,
     *         final_image_path: string,
     *         quality_issues: list<string>
     *     }
     * }
     */
    private function result(
        string $status,
        string $text,
        array $warnings,
        string $runId,
        string $stopReason,
        int $attempts,
        array $steps,
        string $finalImagePath,
        array $qualityIssues,
    ): array {
        return [
            'status' => $status,
            'text' => $text,
            'warnings' => array_values(array_unique($warnings)),
            'meta' => [
                'run_id' => $runId,
                'stop_reason' => $stopReason,
                'attempts' => $attempts,
                'steps' => $steps,
                'final_image_path' => $finalImagePath,
                'quality_issues' => array_values(array_unique($qualityIssues)),
            ],
        ];
    }
}
