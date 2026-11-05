<?php

declare(strict_types=1);

/**
 * Vercel Serverless Entrypoint for Laravel
 * Handles read-only Lambda environment by creating necessary storage paths in /tmp.
 */

// 1. Initialize writable storage in /tmp
$storagePaths = [
    '/tmp/storage/app/public',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/framework/views',
    '/tmp/storage/logs',
];

foreach ($storagePaths as $path) {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

// 2. Setup SQLite database in /tmp if using SQLite
$sourceDb = __DIR__ . '/../database/database.sqlite';
$targetDb = '/tmp/database.sqlite';

if (!file_exists($targetDb)) {
    if (file_exists($sourceDb)) {
        copy($sourceDb, $targetDb);
    } else {
        touch($targetDb);
    }
}

// 3. Set environment variables for /tmp paths
putenv("APP_STORAGE=/tmp/storage");
$_ENV['APP_STORAGE'] = '/tmp/storage';

putenv("VIEW_COMPILED_PATH=/tmp/storage/framework/views");
$_ENV['VIEW_COMPILED_PATH'] = '/tmp/storage/framework/views';

putenv("DB_DATABASE={$targetDb}");
$_ENV['DB_DATABASE'] = $targetDb;

// 4. Delegate request to Laravel public/index.php
require __DIR__ . '/../public/index.php';
