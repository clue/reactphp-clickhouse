<?php

// $ php examples/benchmark-read.php
// $ php examples/benchmark-read.php "SELECT * FROM user WHERE age > 30"

use React\EventLoop\Loop;

require __DIR__ . '/../vendor/autoload.php';

if (extension_loaded('xdebug')) {
    echo 'NOTICE: The "xdebug" extension is loaded, this has a major impact on performance.' . PHP_EOL;
}

$db = new Clue\React\ClickHouse\ClickHouseClient('http://localhost:8123/');

$stream = $db->queryStream($argv[1] ?? 'SELECT toUInt8(number) FROM system.numbers LIMIT 1000000');

$count = 0;
$stream->on('data', function () use (&$count) {
    ++$count;
});
$stream->on('error', function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});

$start = microtime(true);
$report = Loop::addPeriodicTimer(0.05, function () use (&$count, $start) {
    printf("\r%d records in %0.3fs...", $count, microtime(true) - $start);
});

$stream->on('close', function () use (&$count, $report, $start) {
    $now = microtime(true);
    Loop::cancelTimer($report);

    printf("\r%d records in %0.3fs => %d records/s\n", $count, $now - $start, $count / ($now - $start));
});

