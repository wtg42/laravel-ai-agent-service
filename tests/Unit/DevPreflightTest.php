<?php

use Symfony\Component\Process\Process;

function devPreflightRepoRoot(): string
{
    return dirname(__DIR__, 2);
}

function createDevPreflightFixture(array $options = []): string
{
    $root = sys_get_temp_dir().'/dev-preflight-'.bin2hex(random_bytes(6));

    mkdir($root, 0777, true);

    if (($options['vendor'] ?? true) === true) {
        mkdir($root.'/vendor', 0777, true);
        file_put_contents($root.'/vendor/autoload.php', "<?php\n");
    }

    if (($options['env'] ?? true) === true) {
        $appKey = array_key_exists('app_key', $options) ? (string) $options['app_key'] : 'base64:fixture-key';
        file_put_contents($root.'/.env', "APP_KEY={$appKey}\n");
    }

    if (($options['databaseFile'] ?? true) === true) {
        mkdir($root.'/database', 0777, true);
        $databasePath = $root.'/database/database.sqlite';
        file_put_contents($databasePath, '');

        if (($options['databaseTables'] ?? true) === true) {
            $pdo = new PDO('sqlite:'.$databasePath);
            $pdo->exec('CREATE TABLE cache (key TEXT PRIMARY KEY, value TEXT NOT NULL, expiration INTEGER NOT NULL)');
            $pdo->exec('CREATE TABLE cache_locks (key TEXT PRIMARY KEY, owner TEXT NOT NULL, expiration INTEGER NOT NULL)');
            $pdo->exec('CREATE TABLE failed_jobs (id INTEGER PRIMARY KEY AUTOINCREMENT, uuid TEXT NOT NULL UNIQUE)');
            $pdo->exec('CREATE TABLE job_batches (id TEXT PRIMARY KEY, name TEXT NOT NULL)');
            $pdo->exec('CREATE TABLE jobs (id INTEGER PRIMARY KEY AUTOINCREMENT, queue TEXT NOT NULL)');
            $pdo->exec('CREATE TABLE sessions (id TEXT PRIMARY KEY, payload TEXT NOT NULL, last_activity INTEGER NOT NULL)');
        }
    }

    if (($options['nodeModules'] ?? true) === true) {
        mkdir($root.'/node_modules', 0777, true);
    }

    return $root;
}

function runDevPreflight(string $root): Process
{
    $process = new Process([PHP_BINARY, devPreflightRepoRoot().'/bin/dev-preflight.php'], devPreflightRepoRoot(), [
        'DEV_PREFLIGHT_ROOT' => $root,
    ]);

    $process->run();

    return $process;
}

function deleteDirectory(string $path): void
{
    if (! is_dir($path)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($items as $item) {
        if ($item->isDir() && ! $item->isLink()) {
            rmdir($item->getPathname());

            continue;
        }

        unlink($item->getPathname());
    }

    rmdir($path);
}

it('passes when the local dev prerequisites are complete', function () {
    $root = createDevPreflightFixture();

    try {
        $process = runDevPreflight($root);

        expect($process->isSuccessful())->toBeTrue();
        expect($process->getErrorOutput())->toBe('');
    } finally {
        deleteDirectory($root);
    }
});

it('reports every missing prerequisite before exiting', function () {
    $root = createDevPreflightFixture([
        'vendor' => false,
        'app_key' => '',
        'databaseFile' => false,
        'nodeModules' => false,
    ]);

    try {
        $process = runDevPreflight($root);
        $errorOutput = $process->getErrorOutput();

        expect($process->getExitCode())->toBe(1);
        expect($errorOutput)->toContain('Development environment is not ready.');
        expect($errorOutput)->toContain('Composer dependencies are missing.');
        expect($errorOutput)->toContain('APP_KEY is missing or empty in .env.');
        expect($errorOutput)->toContain('SQLite database file is missing.');
        expect($errorOutput)->toContain('Node dependencies are missing.');
        expect($errorOutput)->toContain('composer install');
        expect($errorOutput)->toContain('touch database/database.sqlite');
        expect($errorOutput)->toContain('php artisan migrate --force');
        expect($errorOutput)->toContain('npm install');
    } finally {
        deleteDirectory($root);
    }
});

it('reports a missing .env file with setup guidance', function () {
    $root = createDevPreflightFixture([
        'env' => false,
        'vendor' => false,
        'databaseFile' => false,
        'nodeModules' => false,
    ]);

    try {
        $process = runDevPreflight($root);
        $errorOutput = $process->getErrorOutput();

        expect($process->getExitCode())->toBe(1);
        expect($errorOutput)->toContain('.env is missing.');
        expect($errorOutput)->toContain('Copy `.env.example` to `.env`.');
        expect($errorOutput)->toContain('Run `php artisan key:generate` after creating `.env`.');
    } finally {
        deleteDirectory($root);
    }
});

it('reports missing sqlite tables when the database file exists but is uninitialized', function () {
    $root = createDevPreflightFixture([
        'databaseTables' => false,
    ]);

    try {
        $process = runDevPreflight($root);
        $errorOutput = $process->getErrorOutput();

        expect($process->getExitCode())->toBe(1);
        expect($errorOutput)->toContain(
            'SQLite database is missing required tables: cache, cache_locks, failed_jobs, job_batches, jobs, sessions.'
        );
        expect($errorOutput)->toContain('php artisan migrate --force');
    } finally {
        deleteDirectory($root);
    }
});

it('wires the preflight helper into bin/dev', function () {
    expect(file_get_contents(devPreflightRepoRoot().'/bin/dev'))->toContain('bin/dev-preflight.php');
});
