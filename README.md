# clue/reactphp-clickhouse

Blazing fast access to your [ClickHouse](https://clickhouse.tech/) database,
built on top of [ReactPHP](https://reactphp.org/).

[ClickHouse](https://clickhouse.tech/) is a fast open-source OLAP database management system (DBMS).
It is column-oriented and allows to generate analytical reports using SQL queries in real-time.
Its efficient design and fast operation makes it an ideal candidate when processing very large amounts of data.
This library provides you a simple API to work with your ClickHouse database from within PHP.
It enables you to query and insert data through a very efficient way.
It is written in pure PHP and does not require any extensions.

* **Async execution of queries** -
  Send any number of queries (SQL) to ClickHouse in parallel and
  process their responses as soon as results come in.
  The Promise-based design provides a *sane* interface to working with async results.
* **Streaming queries** -
  Memory-efficient processing of millions of records per second.
* **Lightweight, SOLID design** -
  Provides a thin abstraction that is [*just good enough*](https://en.wikipedia.org/wiki/Principle_of_good_enough)
  and does not get in your way.
* **Good test coverage** -
  Comes with an automated test suite and is regularly tested against actual ClickHouse databases in the wild.

**Table of contents**

* [Quickstart example](#quickstart-example)
* [Install](#install)
* [License](#license)

## Quickstart example

Once [installed](#install), you can use the following code to connect to your
local ClickHouse database and send some queries:

```php
$loop = React\EventLoop\Factory::create();
$client = new Clue\React\ClickHouse\Client('http://localhost:8123/', $loop);

$client->query('SELECT id, name FROM users')->then(function (Clue\React\ClickHouse\Result $result) {
    var_dump($result);
}, function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});

$loop->run();
```

## Install

[![A clue·access project](https://raw.githubusercontent.com/clue-access/clue-access/main/clue-access.png)](https://github.com/clue-access/clue-access)

*This project is currently under active development,
you're looking at a temporary placeholder repository.*

The code is available in early access to my sponsors here: https://github.com/clue-access/reactphp-clickhouse

Do you sponsor me on GitHub? Thank you for supporting sustainable open-source, you're awesome! ❤️ Have fun with the code! 🎉

Seeing a 404 (Not Found)? Sounds like you're not in the early access group. Consider becoming a [sponsor on GitHub](https://github.com/sponsors/clue) for early access. Check out [clue·access](https://github.com/clue-access/clue-access) for more details.

This way, more people get a chance to take a look at the code before the public release.

Rock on 🤘

## License

This project will be released under the permissive [MIT license](LICENSE).

> Did you know that I offer custom development services and issuing invoices for
  sponsorships of releases and for contributions? Contact me (@clue) for details.
