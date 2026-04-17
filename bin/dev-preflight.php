#!/usr/bin/env php
<?php

declare(strict_types=1);

const REQUIRED_SQLITE_TABLES = [
    'cache',
    'cache_locks',
    'failed_jobs',
    'job_batches',
    'jobs',
    'sessions',
];

$root = getenv('DEV_PREFLIGHT_ROOT');
$root = $root !== false && $root !== '' ? rtrim($root, DIRECTORY_SEPARATOR) : dirname(__DIR__);

$issues = [];

if (! is_file($root.'/vendor/autoload.php')) {
    $issues[] = [
        'title' => 'Composer dependencies are missing.',
        'fixes' => [
            'Run `composer install`.',
        ],
    ];
}

$envPath = $root.'/.env';

if (! is_file($envPath)) {
    $issues[] = [
        'title' => '.env is missing.',
        'fixes' => [
            'Copy `.env.example` to `.env`.',
            'Run `php artisan key:generate` after creating `.env`.',
        ],
    ];
} elseif (trim((string) readEnvValue($envPath, 'APP_KEY')) === '') {
    $issues[] = [
        'title' => 'APP_KEY is missing or empty in .env.',
        'fixes' => [
            'Run `php artisan key:generate`.',
        ],
    ];
}

$sqlitePath = $root.'/database/database.sqlite';

if (! is_file($sqlitePath)) {
    $issues[] = [
        'title' => 'SQLite database file is missing.',
        'fixes' => [
            'Run `touch database/database.sqlite`.',
            'Run `php artisan migrate --force`.',
        ],
    ];
} elseif (! extension_loaded('pdo_sqlite')) {
    $issues[] = [
        'title' => 'The pdo_sqlite PHP extension is missing.',
        'fixes' => [
            'Enable the `pdo_sqlite` extension for the local PHP runtime.',
        ],
    ];
} else {
    $missingTables = missingSQLiteTables($sqlitePath, REQUIRED_SQLITE_TABLES);

    if ($missingTables !== []) {
        $issues[] = [
            'title' => sprintf(
                'SQLite database is missing required tables: %s.',
                implode(', ', $missingTables)
            ),
            'fixes' => [
                'Run `php artisan migrate --force`.',
            ],
        ];
    }
}

if (! is_dir($root.'/node_modules')) {
    $issues[] = [
        'title' => 'Node dependencies are missing.',
        'fixes' => [
            'Run `npm install`.',
        ],
    ];
}

if ($issues === []) {
    exit(0);
}

fwrite(STDERR, "Development environment is not ready.\n\n");

foreach ($issues as $issue) {
    fwrite(STDERR, $issue['title']."\n");

    foreach ($issue['fixes'] as $fix) {
        fwrite(STDERR, '- '.$fix."\n");
    }

    fwrite(STDERR, "\n");
}

exit(1);

function readEnvValue(string $envPath, string $key): ?string
{
    $contents = file_get_contents($envPath);

    if ($contents === false) {
        return null;
    }

    foreach (preg_split('/\R/', $contents) as $line) {
        $trimmed = trim((string) $line);

        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }

        if (! str_starts_with($trimmed, $key.'=')) {
            continue;
        }

        $value = substr($trimmed, strlen($key) + 1);

        return trim($value, " \t\n\r\0\x0B\"'");
    }

    return null;
}

/**
 * @param  array<int, string>  $requiredTables
 * @return array<int, string>
 */
function missingSQLiteTables(string $sqlitePath, array $requiredTables): array
{
    $pdo = new PDO('sqlite:'.$sqlitePath);
    $statement = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table'");

    if ($statement === false) {
        return $requiredTables;
    }

    $existingTables = array_fill_keys($statement->fetchAll(PDO::FETCH_COLUMN), true);

    return array_values(array_filter(
        $requiredTables,
        static fn (string $table): bool => ! isset($existingTables[$table])
    ));
}
