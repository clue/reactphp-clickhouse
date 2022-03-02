<?php

// $ php examples/query-stream.php
// $ php examples/query-stream.php "SELECT toUInt8(number) FROM system.numbers LIMIT 1000000"
//
// $ php examples/query-stream.php "WATCH fooc"
// $ php examples/query-stream.php "WATCH fooc EVENTS"

require __DIR__ . '/../vendor/autoload.php';

$db = new Clue\React\ClickHouse\ClickHouseClient('http://localhost:8123/');
$query = isset($argv[1]) ? $argv[1] : 'SELECT `database`, `name`, `engine` FROM system.tables WHERE `database` != \'system\'';

$stream = $db->queryStream($query);

$first = true;
$stream->on('data', function (array $row) use (&$first){
    if ($first) {
        echo implode("\t", array_keys($row)) . PHP_EOL;
        $first = false;
    }
    echo implode("\t", $row) . PHP_EOL;
});

$stream->on('error', function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});

 $stream->on('close', function (){
    echo 'CLOSED' . PHP_EOL;
});
