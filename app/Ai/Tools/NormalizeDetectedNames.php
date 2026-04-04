<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class NormalizeDetectedNames implements Tool
{
    /**
     * @var list<string>
     */
    private array $titlePrefixes = [
        '聯絡人',
        '承辦人',
        '申請人',
        '收件人',
        '寄件人',
        '窗口',
        '聯絡窗口',
        '負責人',
    ];

    /**
     * @var list<string>
     */
    private array $titleSuffixes = [
        '先生',
        '小姐',
        '女士',
        '太太',
        '老師',
        '醫師',
        '博士',
        '律師',
        '經理',
        '主任',
        '總監',
        '店長',
        '組長',
        '科長',
        '處長',
        '局長',
        '董事長',
        '董事',
        '同學',
    ];

    /**
     * @var list<string>
     */
    private array $organizationSuffixes = [
        '局',
        '處',
        '部',
        '司',
        '署',
        '會',
        '所',
        '院',
        '室',
        '科',
        '組',
        '站',
        '隊',
        '行',
        '社',
        '府',
        '校',
        '系',
    ];

    public function description(): Stringable|string
    {
        return 'Normalize detected Chinese names by trimming titles, removing duplicates, and filtering obvious false positives.';
    }

    public function handle(Request $request): Stringable|string
    {
        return json_encode($this->normalize($request->array('names')), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'names' => $schema->array()
                ->items($schema->object([
                    'value' => $schema->string()->required(),
                    'evidence' => $schema->string()->nullable(),
                    'confidence' => $schema->number()->min(0)->max(1)->nullable(),
                ]))
                ->required(),
        ];
    }

    /**
     * @param  list<array{value?: mixed, evidence?: mixed, confidence?: mixed}>  $names
     * @return list<array{value: string, evidence: string, confidence: float}>
     */
    public function normalize(array $names): array
    {
        $normalized = [];

        foreach ($names as $name) {
            $value = $this->normalizeValue((string) ($name['value'] ?? ''));

            if (! $this->isValidChineseName($value)) {
                continue;
            }

            $normalized[$value] ??= [
                'value' => $value,
                'evidence' => $this->normalizeEvidence((string) ($name['evidence'] ?? $value)),
                'confidence' => $this->normalizeConfidence($name['confidence'] ?? 0.0),
            ];

            $normalized[$value]['confidence'] = max(
                $normalized[$value]['confidence'],
                $this->normalizeConfidence($name['confidence'] ?? 0.0),
            );
        }

        return array_values($normalized);
    }

    private function normalizeValue(string $value): string
    {
        $value = Str::of($value)
            ->replace(['　', ' ', "\n", "\r", "\t"], '')
            ->value();

        $value = preg_replace('/^[\p{Z}\p{P}\p{S}]+|[\p{Z}\p{P}\p{S}]+$/u', '', $value) ?? '';

        foreach ($this->titlePrefixes as $prefix) {
            $value = Str::of($value)->after($prefix)->value();
        }

        foreach ($this->titleSuffixes as $suffix) {
            if (Str::endsWith($value, $suffix)) {
                $value = Str::of($value)->beforeLast($suffix)->value();
            }
        }

        return trim($value);
    }

    private function normalizeEvidence(string $evidence): string
    {
        $evidence = preg_replace('/\s+/u', ' ', trim($evidence));

        return $evidence ?: '';
    }

    private function normalizeConfidence(mixed $confidence): float
    {
        $confidence = is_numeric($confidence) ? (float) $confidence : 0.0;

        return max(0.0, min(1.0, $confidence));
    }

    private function isValidChineseName(string $value): bool
    {
        if (! preg_match('/^[\p{Han}]{2,4}$/u', $value)) {
            return false;
        }

        foreach ($this->organizationSuffixes as $suffix) {
            if (Str::endsWith($value, $suffix)) {
                return false;
            }
        }

        return true;
    }
}
