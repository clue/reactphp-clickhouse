<?php

// $ php examples/query.php
// $ php examples/query.php "SELECT toUInt8(number) FROM system.numbers LIMIT 10"
//
// $ php examples/query.php "CREATE TABLE IF NOT EXISTS foos (bar String) ENGINE = Memory"
// $ php examples/query.php "SELECT COUNT(*) AS n FROM foos"
//
// $ php examples/query.php "CREATE LIVE VIEW fooc AS SELECT COUNT(*) FROM foos"
// $ php examples/query.php "WATCH fooc LIMIT 0"

require __DIR__ . '/../vendor/autoload.php';

$db = new Clue\React\ClickHouse\ClickHouseClient('http://localhost:8123/');
$query = isset($argv[1]) ? $argv[1] : 'SELECT `database`, `name`, `engine` FROM system.tables WHERE `database` != \'system\'';

$db->query($query)->then(function (Clue\React\ClickHouse\Result $result) {
    echo 'Found ' . count($result->data) . ' data: ' . PHP_EOL;
    echo implode("\t", array_column($result->meta, "name")) . PHP_EOL;
    foreach ($result->data as $row) {
        echo implode("\t", $row) . PHP_EOL;
    }
}, function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});
