<?php

use App\Ai\Agents\ChineseNameDetectionAgent;
use Illuminate\Testing\Fluent\AssertableJson;

it('detects explicit chinese names and normalizes duplicates', function () {
    ChineseNameDetectionAgent::fake([
        [
            'names' => [
                [
                    'value' => '王小明',
                    'evidence' => '您好，我是王小明。',
                    'confidence' => 0.82,
                ],
                [
                    'value' => '王小明老師',
                    'evidence' => '王小明老師會在下午到場。',
                    'confidence' => 0.91,
                ],
                [
                    'value' => '陳怡君',
                    'evidence' => '今天與陳怡君一起出席會議。',
                    'confidence' => 0.88,
                ],
            ],
        ],
    ])->preventStrayPrompts();

    $response = $this->postJson('/api/pii/chinese-names/detect', [
        'content' => '您好，我是王小明。今天與陳怡君一起出席會議。王小明老師會在下午到場。',
    ]);

    $response
        ->assertSuccessful()
        ->assertJson(fn (AssertableJson $json) => $json->has('names', 2)
            ->has('names.0', fn (AssertableJson $json) => $json->where('value', '王小明')
                ->where('evidence', '您好，我是王小明。')
                ->where('confidence', 0.91)
                ->etc()
            )
            ->has('names.1', fn (AssertableJson $json) => $json->where('value', '陳怡君')
                ->where('evidence', '今天與陳怡君一起出席會議。')
                ->where('confidence', 0.88)
                ->etc()
            )
        );

    ChineseNameDetectionAgent::assertPrompted('您好，我是王小明。今天與陳怡君一起出席會議。王小明老師會在下午到場。');
});

it('returns an empty result when no explicit names are found', function () {
    ChineseNameDetectionAgent::fake([
        [
            'names' => [],
        ],
    ])->preventStrayPrompts();

    $response = $this->postJson('/api/pii/chinese-names/detect', [
        'content' => '本通知由客服中心發出，請留意最新公告。',
    ]);

    $response
        ->assertSuccessful()
        ->assertJson(fn (AssertableJson $json) => $json->has('names', 0)
            ->etc()
        );
});

it('rejects blank content', function () {
    ChineseNameDetectionAgent::fake()->preventStrayPrompts();

    $response = $this->postJson('/api/pii/chinese-names/detect', [
        'content' => '   ',
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['content']);

    ChineseNameDetectionAgent::assertNeverPrompted();
});

it('filters title-only and organization-like false positives', function () {
    ChineseNameDetectionAgent::fake([
        [
            'names' => [
                [
                    'value' => '王先生',
                    'evidence' => '請王先生協助處理。',
                    'confidence' => 0.77,
                ],
                [
                    'value' => '林務局',
                    'evidence' => '林務局已發出通知。',
                    'confidence' => 0.73,
                ],
                [
                    'value' => '李美玲',
                    'evidence' => '聯絡人：李美玲',
                    'confidence' => 0.94,
                ],
            ],
        ],
    ])->preventStrayPrompts();

    $response = $this->postJson('/api/pii/chinese-names/detect', [
        'content' => '請王先生協助處理。林務局已發出通知。聯絡人：李美玲',
    ]);

    $response
        ->assertSuccessful()
        ->assertJson(fn (AssertableJson $json) => $json->has('names', 1)
            ->has('names.0', fn (AssertableJson $json) => $json->where('value', '李美玲')
                ->where('evidence', '聯絡人：李美玲')
                ->where('confidence', 0.94)
                ->etc()
            )
        );
});

it('returns service unavailable when the model provider fails', function () {
    ChineseNameDetectionAgent::fake(function () {
        throw new RuntimeException('Ollama is offline');
    })->preventStrayPrompts();

    $response = $this->postJson('/api/pii/chinese-names/detect', [
        'content' => '您好，我是王小明。',
    ]);

    $response
        ->assertStatus(503)
        ->assertJson(fn (AssertableJson $json) => $json->where('message', 'Chinese name detection is currently unavailable.')
            ->etc()
        );
});
