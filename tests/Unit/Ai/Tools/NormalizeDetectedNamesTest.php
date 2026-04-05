<?php

use App\Ai\Tools\NormalizeDetectedNames;

it('normalizes valid chinese names by trimming prefixes suffixes and whitespace', function () {
    $normalizer = new NormalizeDetectedNames;

    $result = $normalizer->normalize([
        [
            'value' => " 聯絡窗口　王小明老師\n",
            'evidence' => " 聯絡窗口：王小明老師\n",
            'confidence' => 0.82,
        ],
    ]);

    expect($result)->toBe([
        [
            'value' => '王小明',
            'evidence' => '聯絡窗口：王小明老師',
            'confidence' => 0.82,
        ],
    ]);
});

it('filters title only organization like and invalid name values', function () {
    $normalizer = new NormalizeDetectedNames;

    $result = $normalizer->normalize([
        [
            'value' => '王主任',
            'evidence' => '請王主任協助。',
            'confidence' => 0.7,
        ],
        [
            'value' => '資訊部',
            'evidence' => '資訊部將於今日回覆。',
            'confidence' => 0.7,
        ],
        [
            'value' => '林務局',
            'evidence' => '林務局已發出通知。',
            'confidence' => 0.73,
        ],
        [
            'value' => '李美玲',
            'evidence' => '聯絡窗口：李美玲',
            'confidence' => 0.94,
        ],
    ]);

    expect($result)->toBe([
        [
            'value' => '李美玲',
            'evidence' => '聯絡窗口：李美玲',
            'confidence' => 0.94,
        ],
    ]);
});

it('deduplicates normalized names and keeps the highest confidence', function () {
    $normalizer = new NormalizeDetectedNames;

    $result = $normalizer->normalize([
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
    ]);

    expect($result)->toBe([
        [
            'value' => '李美玲',
            'evidence' => '聯絡窗口：李美玲',
            'confidence' => 0.95,
        ],
    ]);
});

it('stabilizes evidence and clamps confidence values', function () {
    $normalizer = new NormalizeDetectedNames;

    $result = $normalizer->normalize([
        [
            'value' => '陳怡君',
            'confidence' => 'not-numeric',
        ],
        [
            'value' => '王小明',
            'evidence' => '   ',
            'confidence' => 1.5,
        ],
        [
            'value' => '林美華',
            'evidence' => ' 林美華 '."\n",
            'confidence' => -2,
        ],
    ]);

    expect($result)->toBe([
        [
            'value' => '陳怡君',
            'evidence' => '陳怡君',
            'confidence' => 0.0,
        ],
        [
            'value' => '王小明',
            'evidence' => '',
            'confidence' => 1.0,
        ],
        [
            'value' => '林美華',
            'evidence' => '林美華',
            'confidence' => 0.0,
        ],
    ]);
});
