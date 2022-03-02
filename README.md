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

* [Support us](#support-us)
* [Quickstart example](#quickstart-example)
* [Usage](#usage)
    * [Database API](#database-api)
    * [Promises](#promises)
    * [Cancellation](#cancellation)
    * [Timeouts](#timeouts)
    * [Blocking](#blocking)
    * [Streaming](#streaming)
* [API](#api)
    * [ClickHouseClient](#clickhouseclient)
        * [query()](#query)
        * [queryStream()](#querystream)
        * [insert()](#insert)
        * [insertStream()](#insertstream)
        * [withSession()](#withsession)
* [Install](#install)
* [Tests](#tests)
* [License](#license)

## Support us

[![A clue·access project](https://raw.githubusercontent.com/clue-access/clue-access/main/clue-access.png)](https://github.com/clue-access/clue-access)

*This project is currently under active development,
you're looking at a temporary placeholder repository.*

The code is available in early access to my sponsors here: https://github.com/clue-access/reactphp-clickhouse

Do you sponsor me on GitHub? Thank you for supporting sustainable open-source, you're awesome! ❤️ Have fun with the code! 🎉

Seeing a 404 (Not Found)? Sounds like you're not in the early access group. Consider becoming a [sponsor on GitHub](https://github.com/sponsors/clue) for early access. Check out [clue·access](https://github.com/clue-access/clue-access) for more details.

This way, more people get a chance to take a look at the code before the public release.

## Quickstart example

Once [installed](#install), you can use the following code to connect to your
local ClickHouse database and send some queries:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

$client = new Clue\React\ClickHouse\ClickHouseClient('http://localhost:8123/');

$client->query('SELECT id, name FROM users')->then(function (Clue\React\ClickHouse\Result $result) {
    var_dump($result);
}, function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});
```

See also the [examples](examples).

## Usage

### Database API

Most importantly, this project provides a [`ClickHouseClient`](#clickhouseclient) object that offers
several methods that allow you to access your ClickHouse database:

```php
$db = new Clue\React\ClickHouse\ClickHouseClient('http://localhost:8123/');

$db->query($query);
$db->insert($table, $data);
```

Each of the above methods supports async operation and either *fulfills* with
its result or *rejects* with an `Exception`.
Please see the following chapter about [promises](#promises) for more details.

### Promises

Sending queries is async (non-blocking), so you can actually send multiple queries in parallel.
ClickHouse will respond to each query with a result, the order is not guaranteed.
Sending queries uses a [Promise](https://github.com/reactphp/promise)-based interface
that makes it easy to react to when a query is completed (i.e. either successfully fulfilled or rejected with an error).

```php
$db->query('SELECT COUNT(*) FROM users')->then(
    function (Clue\React\ClickHouse\Result $result) {
        // results received
    },
    function (Exception $e) {
        // an error occured while executing the query
    }
});
```

If this looks strange to you, you can also use the more traditional [blocking API](#blocking).

### Cancellation

The returned Promise is implemented in such a way that it can be cancelled
when it is still pending.
Cancelling a pending promise will reject its value with an Exception and
clean up any underlying resources.

```php
$promise = $db->query('SELECT COUNT(*) FROM users');

Loop::addTimer(2.0, function () use ($promise) {
    $promise->cancel();
});
```

### Timeouts

This library uses a very efficient HTTP implementation, so most queries
should usually be completed in mere milliseconds. However, when sending queries
over an unreliable network (the internet), there are a number of things
that can go wrong and may cause the request to fail after a time. As such,
timeouts are handled by the underlying HTTP library and this library respects
PHP's `default_socket_timeout` setting (default 60s) as a timeout for sending the
outgoing query and waiting for a successful result and will otherwise
cancel the pending request and reject its value with an `Exception`.

Note that this timeout value covers creating the underlying transport connection,
sending the request, waiting for the database to process the request
and receiving the full response. To use a custom timeout value, you can
pass the timeout to the [underlying `Browser`](https://github.com/reactphp/http#timeouts)
like this:

```php
$browser = new React\Http\Browser();
$browser = $browser->withTimeout(10.0);

$db = new Clue\React\ClickHouse\ClickHouseClient($url, $browser);

$db->query('SELECT COUNT(*) AS count FROM users')->then(function (Clue\React\ClickHouse\Result $result) {
    // results received within 10 seconds maximum
    echo 'Number of users: '$result->data[0]['count'] . PHP_EOL;
});
```

Similarly, you can use a negative timeout value to not apply a timeout at all
or use a `null` value to restore the default handling. Note that the underlying
connection may still impose a different timeout value. See also the underlying
[timeouts documentation](https://github.com/reactphp/http#timeouts) for more details.

### Blocking

As stated above, this library provides you a powerful, async API by default.

If, however, you want to integrate this into your traditional, blocking environment,
you should look into also using [clue/reactphp-block](https://github.com/clue/reactphp-block).

The resulting blocking code could look something like this:

```php
use Clue\React\Block;
use React\EventLoop\Loop;

$db = new Clue\React\ClickHouse\ClickHouseClient('http://localhost:8123/');

$promise = $db->query('SELECT COUNT(*) FROM users');

try {
    $result = Block\await($promise, Loop::get());
    // results received
} catch (Exception $e) {
    // an error occured while executing the query
}
```

Similarly, you can also process multiple queries concurrently and await an array of results:

```php
$promises = [
    $db->query('SELECT COUNT(*) FROM users'),
    $db->query('SELECT name, email FROM users ORDER BY name LIMIT 10')
];

$results = Block\awaitAll($promises, Loop::get());
```

Please refer to [clue/reactphp-block](https://github.com/clue/reactphp-block#readme) for more details.

### Streaming

The following API endpoint exposes the result set as an object containing all rows:

```php
$db->query($query);
````

Keep in mind that this means the whole result set has to be kept in memory.
This is easy to get started and works reasonably well for smaller result sets.

For bigger result sets it's usually a better idea to use a streaming approach,
where only small chunks have to be kept in memory.
This works for (any number of) rows of arbitrary sizes.

The [`ClickHouseClient::queryStream()`](#querystream) method complements the default
Promise-based [`ClickHouseClient::query()`](#query) API and returns an instance implementing
[`ReadableStreamInterface`](https://github.com/reactphp/stream#readablestreaminterface) instead:

```php
$stream = $db->queryStream('SELECT name, email FROM users');

$stream->on('data', function (array $row) {
    echo $row['name'] . ': ' . $row['email'] . PHP_EOL;
});

$stream->on('error', function (Exception $error) {
    echo 'Error: ' . $error->getMessage() . PHP_EOL;
});

$stream->on('close', function () {
    echo '[DONE]' . PHP_EOL;
});
```

The [`ClickHouseClient::insertStream()`](#insertstream) method complements the default
Promise-based [`ClickHouseClient::insert()`](#insert) API and returns an instance implementing
[`WritableStreamInterface`](https://github.com/reactphp/stream#writablestreaminterface) instead:

```php
$stream = $db->insertStream('users');

$stream->write(['name' => 'Alice', 'email' => 'alice@example.com']);
$stream->end(['name' => 'Bob', 'email' => 'bob@example.com']);

$stream->on('error', function (Exception $error) {
    echo 'Error: ' . $error->getMessage() . PHP_EOL;
});

$stream->on('close', function () {
    echo '[CLOSED]' . PHP_EOL;
});
```

## API

### ClickHouseClient

The `ClickHouseClient` is responsible for communicating with your ClickHouse database
and for sending your database queries and exposing results from the database.

Its constructor simply requires the URL to your ClickHouse database:

```php
$db = new Clue\React\ClickHouse\ClickHouseClient('http://localhost:8123/');
```

This class takes an optional `Browser|null $browser` parameter that can be used to
pass the browser instance to use for this object.
If you need custom connector settings (DNS resolution, TLS parameters, timeouts,
proxy servers etc.), you can explicitly pass a custom instance of the
[`ConnectorInterface`](https://github.com/reactphp/socket#connectorinterface)
to the [`Browser`](https://github.com/reactphp/http#browser) instance
and pass it as an additional argument to the `ClickHouseClient` like this:

```php
$connector = new React\Socket\Connector([
    'dns' => '127.0.0.1',
    'tcp' => [
        'bindto' => '192.168.10.1:0'
    ],
    'tls' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

$browser = new React\Http\Browser($connector);
$db = new Clue\React\ClickHouse\ClickHouseClient('http://localhost:8123/', $browser);
```

#### query()

The `query(string $sql, array $params = []): PromiseInterface<Result,RuntimeException>` method can be used to 
perform an async query.

```php
$db->query('SELECT name, email FROM users')->then(function (Clue\React\ClickHouse\Result $result) {
    echo count($result->data) . ' row(s) in set' . PHP_EOL;
    foreach ($result->data as $user) {
       echo $user['name'] . ': ' . $user['email'] . PHP_EOL;
    }
}, function (Exception $error) {
    // the query was not executed successfully
    echo 'Error: ' . $error->getMessage() . PHP_EOL;
});
```

You can optionally pass an array of `$params` that will be bound to the
query like this:

```php
$promise = $db->query(
    'SELECT name, email FROM users WHERE name LIKE {search:String}',
    ['search' => '%a%']
);
```

#### queryStream()

The `queryStream(string $sql, array $params = []): ReadableStreamInterface<array>` method can be used to 
perform a streaming query.

```php
$stream = $db->queryStream('SELECT name, email FROM users');

$stream->on('data', function (array $row) {
    echo $row['name'] . ': ' . $row['email'] . PHP_EOL;
});

$stream->on('error', function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});
```

You can optionally pass an array of `$params` that will be bound to the
query like this:

```php
$stream = $db->queryStream(
    'SELECT name, email FROM users WHERE name LIKE {search:String}',
    ['search' => '%a%']
);
```

#### insert()

The `insert(string $table, array $data): PromiseInterface<void,RuntimeException>` method can be used to 
insert a new row.

```php
$db->insert('users', [
    'name' => 'Alice',
    'email' => 'alice@example.com'
]);
```

#### insertStream()

The `insertStream(string $table): WritableStreamInterface<array<string,mixed>>` method can be used to 
insert any number of rows from a stream.

```php
$stream = $db->insertStream('users');

$stream->write([
    'name' => 'Alice',
    'email' => 'alice@example.com'
]);
$stream->end();
```

#### withSession()

The `withSession(?string = null): self` method can be used to 
assign a session identifier to use for all subsequent queries.

```php
$db = $db->withSession();
```

Optionally, you can an explicit session identifier to use.
If you do not pass an explicit session identifier, a random session
identifier will be used.

```php
$db = $db->withSession('imports');
```

You can unset the session identifier by passing an empty string. The new
client will no longer use a session identifier for any subsequent queries.

```php
$db = $db->withSession('');
```

## Install

The recommended way to install this library is [through Composer](https://getcomposer.org/).
[New to Composer?](https://getcomposer.org/doc/00-intro.md)

This project does not yet follow [SemVer](https://semver.org/).
This will install the latest supported version:

While in [early access](#support-us), you first have to manually change your
`composer.json` to include these lines to access the supporters-only repository:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/clue-access/reactphp-clickhouse"
        }
    ]
}
```

Then install this package as usual:

```bash
$ composer require clue/reactphp-clickhouse:dev-main
```

This project aims to run on any platform and thus does not require any PHP
extensions and supports running on PHP 7.0 through current PHP 8+.

## Tests

To run the test suite, you first need to clone this repo and then install all
dependencies [through Composer](https://getcomposer.org/):

```bash
$ composer install
```

To run the test suite, go to the project root and run:

```bash
$ vendor/bin/phpunit
```

The test suite contains both unit tests and functional integration tests.
The functional tests require access to a running ClickHouse database server
instance and will be skipped by default.
If you want to also run the functional tests, you need to supply *your* ClickHouse
database credentials in an environment variable like this:

```bash
$ URL=http://localhost:8123 vendor/bin/phpunit
```

You can run a temporary ClickHouse database server in a Docker container like this:

```
$ docker run -it --rm --net=host yandex/clickhouse-server
```


## License

This project is released under the permissive [MIT license](LICENSE).

> Did you know that I offer custom development services and issuing invoices for
  sponsorships of releases and for contributions? Contact me (@clue) for details.
