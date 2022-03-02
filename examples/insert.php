<?php

require __DIR__ . '/../vendor/autoload.php';

$db = new Clue\React\ClickHouse\ClickHouseClient('http://localhost:8123/');

// $promise = $db->query('CREATE TABLE IF NOT EXISTS foos (bar String) ENGINE = Memory');
// $promise->then(null, 'printf');

$value = isset($argv[1]) ? $argv[1] : date(DATE_ATOM);
$db->insert('foos', ['bar' => $value])->then(function () use ($value) {
    echo 'Inserted: ' . $value . PHP_EOL;
}, function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});
