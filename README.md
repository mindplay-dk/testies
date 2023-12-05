mindplay/testies
================

[![PHP Version](https://img.shields.io/badge/php-8.1%2B-blue.svg)](https://packagist.org/packages/mindplay/testies)
[![Build Status](https://github.com/mindplay-dk/testies/actions/workflows/ci.yml/badge.svg)](https://travis-ci.org/mindplay-dk/testies)

Yeah, testies: a lightweight library of functions for quick, simple unit-testing.

Tries to honor the [Go language philosophy of testing](http://golang.org/doc/faq#How_do_I_write_a_unit_test) - paraphrasing:

> testing frameworks tend to develop into mini-languages of their own, with conditionals and controls and printing
> mechanisms, but PHP already has all those capabilities; why recreate them? We'd rather write tests in PHP; it's one
> fewer language to learn and the approach keeps the tests straightforward and easy to understand.

The primary test API is a set of functions in the `mindplay\testies` namespace.

Internally, the API is backed by a pair of simple "driver" and configuration classes - these are left
as open as possible, and you should feel comfortable extending these and tailoring them specifically to suit the
test-requirements for whatever you're testing.


## Usage

Install via Composer:

    composer require-dev mindplay/testies

Then create a test script - the format is pretty simple:

```php
<?php

// import the functions you need:

use function mindplay\testies\{test, ok};

// bootstrap Composer:

require dirname(__DIR__) . '/vendor/autoload.php';

// define your tests:

test(
    'Describe your test here',
    function () {
        ok(true, 'it works!');
    }
);

// run your tests:

exit(run()); // exits with errorlevel (for CI tools etc.)
```

You can call `test()` as many times as you need to - the tests will queue up, and execute when you call `run()`.


### API

The following functions are available in the `mindplay\testies` namespace:

```php
# Assertions:

ok($result, $why, $value);                 # check the result on an expression
eq($value, $expected, $why);               # check value for strict equality with an expected value
expect($exception_type, $why, $function);  # check for an expected exception that should be thrown 

# Helper functions:

invoke($object, $method_name, $arguments); # invokes a protected or private method; returns the result
inspect($object, $property_name);          # returns a protected or private property value
format($value, $detailed = false);         # format a value for use in diagnostic messages
```

Rather than providing hundreds of assertion functions, you perform assertions using PHP expressions,
often in concert with your own helper functions, or built-in standard functions in PHP - some examples:

```php
test(
    'Various things of great importance',
    function () {
        ok($foo instanceof Foo);              # type-checking an object
        ok(is_int(inspect($foo, '_count')));  # type-checking a private property
        ok(123 == '123');                     # loose comparison
        ok(in_array('b', ['a','b','c']));     # check for presence of a value
        ok(isset($map['key']));               # check for presence of a key
        ok(is_string(@$map['key']));          # type-check a key/value with error-suppression
    }
);
```

We find that idiomatic PHP code is something you already know - rather than inventing our own
domain-specific language for testing, we believe in writing tests that more closely resemble
ordinary everyday code.


#### Custom Assertion Functions

Your custom assertion functions, like the built-in assertion functions, are just functions - usually
these will call back to the `ok()` function to report the result. For example:

```php
/**
 * Assert that a numeric value is very close to a given expected value 
 * 
 * @param float|int $value    actual value
 * @param float|int $expected expected near value
 * @param int       $decimals number of decimals of error tolerance
 */
function nearly($value, $expected, $decimals = 8) {
    ok(abs($value - $expected) * pow(10, $decimals) <= 1, "{$value} should be nearly {$expected}", $value);
}

test(
    'Values should be approximately right',
    function () {
        nearly(9.999999999, 10);
        nearly(10.000000001, 10);
        nearly(10.00002, 10);
    }
);
```

You can use the same approach to group multiple assertions for reuse:

```php
function checkValue($value) {
    ok(is_int($value), "value should be numeric", $value);
    ok($value > 0, "value should be positive", $value);
}

test(
    'Checking out some numbers',
    function () {
        checkValue(123);
        checkValue(-1);
    }
);
```

Note that the diagnostic output will always refer to the line number in the test-closure that
generated the assertion result.


## Test Server

⚠️ *This feature is still rough.*

PHP provides a [built-in development web server](http://php.net/manual/en/features.commandline.webserver.php). 

For basic integration tests, a simple wrapper class to launch and shut down a server is provided - the
following example uses `nyholm/psr7` and the `zaphyr-org/http-client` client library:

```php
use Nyholm\Psr7\Factory\Psr17Factory;
use Zaphyr\HttpClient\Client;
use function mindplay\testies\{test, ok, eq};

$server = new TestServer(__DIR__, 8088);

test(
    'Can get home page',
    function () {
        $server = new TestServer(__DIR__, 8088);

        $http = new Psr17Factory();

        $client = new Client($http, $http);

        $response = $client->sendRequest($http->createRequest("GET", "http://127.0.0.1:8088/index.php"));

        eq($response->getStatusCode(), 200);

        ok(strpos($response->getBody(), '<title>Welcome</title>') !== false, "it should capture the response body");
    }
);
```

Note that the server is automatically shut down when the `$server` object falls out of scope - if you
need to explicitly shut down a server, just destroy the server object with e.g. `unset($server)`.

Keep in mind that starting and stopping many server instances can slow down your test drastically - it's
often a good idea to open one server instance and share it between test-functions. Creating and disposing
of clients, on the other hand, is recommended, as sharing client state could lead to unreliable tests. 


## Options

A few simple configuration options are provided via the `configure()` function, which provides access
to the current instance of `TestConfiguration`.


### Code Coverage

To enable code coverage and display the summary result on the console:

```php
configure()->enableCodeCoverage();
```

To output a `clover.xml` file for integration with external analysis tools, specify an output path:

```php
configure()->enableCodeCoverage(__DIR__ . '/build/clover.xml');
```

To enable code coverage analysis only for files in certain folders, pass a path (or array of paths) like so:

```php
configure()->enableCodeCoverage(__DIR__ . '/build/clover.xml', dirname(__DIR__) . '/src');
```


### Verbosity

By default, test output does not produce messages about successful assertions, only failures - if you
want more stuff to look at, enable verbose output:

```php
configure()->enableVerboseOutput();
```

You can also enable this from the command line with the `-v` or `--verbose` switch.


### Strict Error Handling

By default, all PHP errors/warnings/notices are automatically mapped to exceptions via a simple
built-in error handler. If you're testing something that has custom error handling, you can disable it with:

```php
configure()->disableErrorHandler();
```


## Extensibility

The procedural API is actually a thin layer over two classes providing the actual library implementation.

One common reason to use a custom driver, is to override the `TestDriver::format()` method, to customize
how special objects are formatted for output on the console.

To use a custom, derived `TestConfiguration` class:

```php
// Derive your custom configuration class:

class MyTestConfiguration extends TestConfiguration
{
    // ...
}

// Head off your test by selecting your custom configuration object:

configure(new MyTestConfiguration);
```

Then proceed with business as usual.

To use a custom, derived `TestDriver` class: 

```php
// Derive your custom driver class:

class MyTestDriver extends TestDriver
{
    // ...
}

// Boostrap your test by selecting your custom driver:

configure(new TestConfiguration(new TestDriver));
```

Alternatively, create a configuration class that provides a custom default driver class:

```php
class MyTestDriver extends TestDriver
{
    // ...
}

class MyTestConfiguration extends TestConfiguration
{
    protected function createDefaultDriver()
    {
        return new MyTestDriver();        
    }
    
    // ...
}

configure(new MyTestConfiguration);
```

Refer to the actual implementations to see what else is possible - pretty much everything is `public`
or `protected` in these classes, left open for you to call or override as needed.
