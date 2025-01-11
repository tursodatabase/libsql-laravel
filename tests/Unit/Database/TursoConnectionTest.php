<?php

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Libsql\Laravel\Database\TursoPDO;
use Libsql\Laravel\Jobs\TursoSyncJob;


test('it can enable query logging feature', function () {
    DB::connection('turso')->enableQueryLog();

    expect(DB::connection('turso')->logging())->toBeTrue()
        ->and(DB::connection('turso')->tursoPdo()->getClient()->logging())->toBeTrue();
})->group('TursoConnectionTest', 'UnitTest');

test('it can disable query logging feature', function () {
    DB::connection('turso')->disableQueryLog();

    expect(DB::connection('turso')->logging())->toBeFalse()
        ->and(DB::connection('turso')->tursoPdo()->getClient()->logging())->toBeFalse();
})->group('TursoConnectionTest', 'UnitTest');

test('it can get the query log', function () {
    DB::connection('turso')->enableQueryLog();

    $log = DB::connection('turso')->getQueryLog();

    expect($log)->toBeArray()
        ->and($log)->toHaveCount(0);
})->group('TursoConnectionTest', 'UnitTest');

test('it can flush the query log', function () {
    DB::connection('turso')->enableQueryLog();

    DB::connection('turso')->flushQueryLog();

    $log = DB::connection('turso')->getQueryLog();

    expect($log)->toBeArray()
        ->and($log)->toHaveCount(0);
})->group('TursoConnectionTest', 'UnitTest');

test('it will replace the https protocol in database url to be libsql protocol', function () {
    config([
        'database.connections.turso.db_url' => 'https://project-name.turso.io',
    ]);

    expect(DB::connection('turso')->getConfig('db_url'))->toBe('libsql://project-name.turso.io');
})->group('TursoConnectionTest', 'UnitTest');
