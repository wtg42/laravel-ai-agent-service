<?php

test('the project requires php 8.4 in composer', function () {
    $composerPath = __DIR__.'/../../composer.json';

    expect(file_exists($composerPath))->toBeTrue();

    $composer = json_decode(file_get_contents($composerPath), true, flags: JSON_THROW_ON_ERROR);

    expect($composer['require']['php'])->toBe('^8.4');
    expect($composer['require-dev']['laravel/boost'])->toBe('^2.4');
});

test('the project pins php 8.5.4 in mise', function () {
    $misePath = __DIR__.'/../../mise.toml';

    expect(file_exists($misePath))->toBeTrue();
    expect(file_get_contents($misePath))->toContain('php = "8.5.4"');
});
