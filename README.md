# BabelCache

BabelCache is a feature-complete caching library for PHP 5.3+. It supports a
wide range of adapters, namespaced caching and provides an experimental
support for the [PSR Cache Proposal](https://github.com/php-fig/fig-standards/pull/96).

[![Build Status][travisimg]][travis]

[Documentation](http://docs.webvariants.de/babelcache/2.0/index.html) |
[API](http://docs.webvariants.de/babelcache/2.0/api/index.html) |
[Source Code](http://bitbucket.org/webvariants/babelcache/src) |
[Bug Tracker](http://bitbucket.org/webvariants/babelcache/issues)

Supported caching backends are:

* [APC](http://www.php.net/manual/en/book.apc.php)
* [AWS ElastiCache](http://aws.amazon.com/elasticache/)
* Blackhole (useful for transparently disabling caching)
* Filesystem
* [Memcached](http://memcached.org/) (``php_memcache``, ``php_memcached`` and a
  PHP-only implementation for memcached with authentification via SASL)
* [MariaDB & MySQL](https://mariadb.org/)
* Memory (runtime caching only)
* [Redis](http://redis.io/) (requires the pure PHP ``predis/prdis`` library)
* [SQLite](http://www.sqlite.org/)
* [WinCache](http://www.iis.net/downloads/microsoft/wincache-extension)
* [XCache](http://xcache.lighttpd.net/)
* [ZendServer](http://files.zend.com/help/Zend-Platform/zend_cache_api.htm)

You can store arbitrary elements (with the exception of resources and Closures).
BabelCache will always respect their types, so that when you store an int, you
will get an int back.

## Installation

Add the following requirements to your `composer.json`:

    :::json
    {
        "require": {
            "webvariants/babelcache": "$VERSION"
        }
    }

Replace `$VERSION` with one of the available versions on
[Packagist](https://packagist.org/packages/webvariants/babelcache). Use
``composer update`` to install BabelCache and the Composer autoloader to load
it.

## Usage

In most cases, you will want to use the factory to create the caching adapter
for you. You can either use the prepared ``wv\BabelCache\SimpleFactory`` or, if
you need more control, extend the ``wv\BabelCache\Factory``.

    ::::php

    <?php

    $factory = new wv\BabelCache\SimpleFactory();
    $adapter = $factory->getAdapter('apc');

    $adapter->set('world', 'dominated');

Of course you can also instantiate all classes on your own, if you need to.

## Overview

BabelCache is divided into four parts:

### Adapters

Adapters are the basic building block of BabelCache. There is one adapter per
technology (APC, Memcached, Redis, ...) and each of them just implements a
very basic **key-value interface** (``set``, ``get``, ``delete``, ``exists``,
``clear``).

You are free to use the adapters and hence a flat key-value storage, if that
suits your needs. For example:

    ::::php

    <?php

    $factory = new MyFactory(); // extends wv\BabelCache\Factory
    $adapter = $factory->getAdapter('apc');

    $adapter->set('key', 'value');

    print $adapter->get('key'); // prints 'value'

### Caches

A cache implements the advanced **namespace interface**, where elements are
grouped by namespaces and key. This allows for partial flushes. See the full
[documentation](http://docs.webvariants.de/babelcache/2.0/index.html) for more
information on this concept.

There is the ``Generic`` implementation, which uses a key-value adapter to build
a namespaced system on top of it. This adds some overhead, as namespaces are
versioned and the cache has to perform more roundtrips to the backend.
For this reason, adapters which would support a structured storage by themselves,
for example the filesystem or memory adapter, have a counterpart caching class.
So the filesystem adapter has a specific filesystem cache implementation, making
use of directories to manage the namespaces.

In general, the factory takes automatically care of constructing the optimal
implementation. However, you can force the factory to create a bad combination
of generic caching and an adapter.

Constructing caches is as easy as constructing adapters:

    ::::php

    <?php

    $factory = new MyFactory();                  // extends wv\BabelCache\Factory
    $adapter = $factory->getCache('apc');        // returns a Generic instance wrapping the APC adapter
    $adapter = $factory->getCache('filesystem'); // returns a FilesystemCache instance

    $adapter->set('my.awesome.namespace', 'key', 'value');

    print $adapter->get('my.awesome.namespace', 'key'); // prints 'value'

### PSR Wrapper

BabelCache does not follow the PSR proposal. It skips the ``Item`` objects and
has all methods on the services rather than on the items. We believe the way to
encapsulate cache data in objects is a needless overhead.

However, to use BabelCache in a PSR world, you can optionally wrap BabelCache
to make it PSR compliant. For this, the implementations in ``wv\BabelCache\Psr``
are used.

    ::::php

    <?php

    $factory = new MyFactory();
    $pool    = $factory->getPsrPool('apc');  // returns a Psr\Generic\Pool instance wrapping the APC adapter

    $pool->getItem('mykey')->set('value');

    $item = $pool->getItem('mykey'); // returns an Item
    print $item->get();

**Note:** The PSR proposal is still just a proposal, so there are no official
interfaces yet. Until those are available, you have to provide them yourself,
as BabelCache only contains their implementations.

### Decorators

Additionally, there are a few decorators available, which add additional
behaviour on top of caches, like the ``Expiring`` cache, which transparently
encodes an expiry into the cache values.

To use a decorator, just apply it to your cache instance. You can mix
decorators, if you need to.

    ::::php

    <?php

    $factory = new MyFactory();
    $cache   = $factory->getCache('apc');

    // make the cache handle timeouts, using 10s as the default timeout
    $cache = new wv\BabelCache\Decorator\Expiring($cache, 10);

    // set a value that expires in 10 seconds
    $cache->set('name.space', 'key', 'value');

    // set a value that expires in 42 seconds
    $cache->set('name.space', 'key', 'value', 42);

    // bring back the old BabelCache 1.x interface
    $cache = new wv\BabelCache\Decorator\Compat($cache);
    $cache->flush('name', true); // it's clear() now

### Jailing

Often you will want to have a simple key-value cache for one of your
components. If you, you *could* just give them a cache adapter and be
done. Unfortunately, this will suck hard when it comes to clearing the
cache (since "clear" in APC actually means "wipe the whole userland
cache").

To make sure you can use multiple cache adapters to the same storage and
still have namespaced cache clearing, you can use the Jailed adapter.
This adapter simply jails a regular, full-blown cache to a fixed
namespace and will only ever clear that one.

    ::::php

    <?php

    $factory = new MyFactory();
    $adapter = $factory->getAdapter('apc');

    // The following will work just fine, if you can live with service A
    // wiping the cache from service B:

    $imaginaryServiceA = new MyFooService($adapter);
    $imaginaryServiceB = new MyBarService($adapter);

    // to jail them, you can wrap a cache like this:
    // (The third argument controls whether clearing will be recursive or not)

    $cache    = $factory->getCache('apc');
    $adapterA = new wv\BabelCache\Adapter\Jailed($cache, 'service.foo', true);
    $adapterB = new wv\BabelCache\Adapter\Jailed($cache, 'service.bar', true);

    $imaginaryServiceA = new MyFooService($adapterA);
    $imaginaryServiceB = new MyBarService($adapterB);

    // Now imaginaryServiceA can clear its cache until it's blue in the face
    // without affecting the other service's cache.

License
-------

BabelCache is licensed under the MIT License - see the LICENSE file for details.

[travis]: https://secure.travis-ci.org/xrstf/babelcache
[travisimg]: https://secure.travis-ci.org/xrstf/babelcache.png
