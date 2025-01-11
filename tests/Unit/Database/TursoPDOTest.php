<?php

use Illuminate\Support\Facades\DB;
use Libsql\Laravel\Database\TursoPDO;

beforeEach(function () {
    $this->pdo = DB::connection()->getPdo();
});

test('it can manage the last insert id value', function () {
    $this->pdo->setLastInsertId(value: 123);

    expect($this->pdo->lastInsertId())->toBe('123');
})->group('TursoPDOTest', 'UnitTest');
