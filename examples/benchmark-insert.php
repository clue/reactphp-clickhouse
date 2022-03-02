<?php

// first create a memory table to store the data:
// $ php examples/query.php "CREATE TABLE IF NOT EXISTS foos (bar String) ENGINE = Memory"
//
// then run the benchmark to insert N rows:
// $ php examples/benchmark-insert.php 1000000

use React\EventLoop\Loop;

require __DIR__ . '/../vendor/autoload.php';

if (extension_loaded('xdebug')) {
    echo 'NOTICE: The "xdebug" extension is loaded, this has a major impact on performance.' . PHP_EOL;
}

$db = new Clue\React\ClickHouse\ClickHouseClient('http://localhost:8123/');

$n = isset($argv[1]) ? $argv[1] : 1000000;

$stream = $db->insertStream('foos');

$count = 0;
$fill = function () use (&$count, $n, $stream, &$fill) {
    $continue = true;
    while ($count < $n && $continue === true) {
        $continue = $stream->write(['bar' => 'now ' . mt_rand()]);
        ++$count;
    }

    if ($count < $n) {
        //echo 'stop after ' . $count.'/'.$n . PHP_EOL;
        $stream->once('drain', function () use ($n, $fill) {
            //echo 'continue' . PHP_EOL;
            Loop::futureTick($fill);
        });
    } else {
        //echo 'done' . $count . PHP_EOL;
        $stream->end();
    }
};
$fill();

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
