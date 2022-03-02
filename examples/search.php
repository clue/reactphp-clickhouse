<?php

require __DIR__ . '/../vendor/autoload.php';

$db = new Clue\React\ClickHouse\ClickHouseClient('http://localhost:8123/');
$search = isset($argv[1]) ? $argv[1] : 'foo';

$db->query(
    'SELECT * FROM foos WHERE bar LIKE {search:String}',
    ['search' => '%' . $search . '%']
)->then(function (Clue\React\ClickHouse\Result $result) {
    echo 'Found ' . count($result->data) . ' rows: ' . PHP_EOL;
    echo implode("\t", array_column($result->meta, 'name')) . PHP_EOL;
    foreach ($result->data as $row) {
        echo implode("\t", $row) . PHP_EOL;
    }
}, function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});
