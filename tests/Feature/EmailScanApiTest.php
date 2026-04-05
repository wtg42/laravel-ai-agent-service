<?php

use App\Ai\Agents\EmailScanAgent;
use Illuminate\Testing\Fluent\AssertableJson;

it('detects a single explicit chinese name from email text', function () {
    EmailScanAgent::fake([
        [
            'names' => [
                [
                    'value' => '王小明',
                    'evidence' => '您好，我是王小明，負責這次專案。',
                    'confidence' => 0.92,
                ],
            ],
        ],
    ])->preventStrayPrompts();

    $response = $this->postJson('/api/pii/email-scan', [
        'content' => "您好，我是王小明，負責這次專案。\n敬請回覆。",
    ]);

    $response
        ->assertSuccessful()
        ->assertJson(fn (AssertableJson $json) => $json->has('names', 1)
            ->has('names.0', fn (AssertableJson $json) => $json->where('value', '王小明')
                ->where('evidence', '您好，我是王小明，負責這次專案。')
                ->where('confidence', 0.92)
                ->etc()
            )
        );

    EmailScanAgent::assertPrompted('您好，我是王小明，負責這次專案。 敬請回覆。');
});

it('detects multiple explicit chinese names from email text', function () {
    EmailScanAgent::fake([
        [
            'names' => [
                [
                    'value' => '王小明',
                    'evidence' => '寄件人：王小明',
                    'confidence' => 0.81,
                ],
                [
                    'value' => '陳怡君',
                    'evidence' => '請與陳怡君確認報價。',
                    'confidence' => 0.89,
                ],
            ],
        ],
    ])->preventStrayPrompts();

    $response = $this->postJson('/api/pii/email-scan', [
        'content' => '寄件人：王小明。請與陳怡君確認報價。',
    ]);

    $response
        ->assertSuccessful()
        ->assertJson(fn (AssertableJson $json) => $json->has('names', 2)
            ->has('names.0', fn (AssertableJson $json) => $json->where('value', '王小明')->etc())
            ->has('names.1', fn (AssertableJson $json) => $json->where('value', '陳怡君')->etc())
        );
});

it('returns an empty result when no explicit names are found in email text', function () {
    EmailScanAgent::fake([
        [
            'names' => [],
        ],
    ])->preventStrayPrompts();

    $response = $this->postJson('/api/pii/email-scan', [
        'content' => '本信件由客服中心寄出，請留意最新公告。',
    ]);

    $response
        ->assertSuccessful()
        ->assertJson(fn (AssertableJson $json) => $json->has('names', 0)->etc());
});

it('rejects blank email content', function () {
    EmailScanAgent::fake()->preventStrayPrompts();

    $response = $this->postJson('/api/pii/email-scan', [
        'content' => " \n\t ",
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['content']);

    EmailScanAgent::assertNeverPrompted();
});

it('filters title-only organization-like and duplicate values in email scans', function () {
    EmailScanAgent::fake([
        [
            'names' => [
                [
                    'value' => '王主任',
                    'evidence' => '請王主任協助。',
                    'confidence' => 0.74,
                ],
                [
                    'value' => '資訊部',
                    'evidence' => '資訊部將於今日回覆。',
                    'confidence' => 0.7,
                ],
                [
                    'value' => '李美玲',
                    'evidence' => '聯絡窗口：李美玲',
                    'confidence' => 0.86,
                ],
                [
                    'value' => '李美玲老師',
                    'evidence' => '李美玲老師已確認出席。',
                    'confidence' => 0.95,
                ],
            ],
        ],
    ])->preventStrayPrompts();

    $response = $this->postJson('/api/pii/email-scan', [
        'content' => '請王主任協助。資訊部將於今日回覆。聯絡窗口：李美玲。李美玲老師已確認出席。',
    ]);

    $response
        ->assertSuccessful()
        ->assertJson(fn (AssertableJson $json) => $json->has('names', 1)
            ->has('names.0', fn (AssertableJson $json) => $json->where('value', '李美玲')
                ->where('evidence', '聯絡窗口：李美玲')
                ->where('confidence', 0.95)
                ->etc()
            )
        );
});

it('returns service unavailable when the email scan provider fails', function () {
    EmailScanAgent::fake(function () {
        throw new RuntimeException('Ollama is offline');
    })->preventStrayPrompts();

    $response = $this->postJson('/api/pii/email-scan', [
        'content' => '您好，我是王小明。',
    ]);

    $response
        ->assertStatus(503)
        ->assertJson(fn (AssertableJson $json) => $json->where('message', 'Email scan is currently unavailable.')
            ->etc()
        );
});
