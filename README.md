# BabelCache

BabelCache is a feature-complete caching library for PHP 5.3+. It supports a
wide range of adapters, namespaced caching and provides an experimental
support for the [PSR Cache Proposal](https://github.com/php-fig/fig-standards/pull/96).

**BabelCache 2.0 is still in development and not yet ready!**

Supported caching backends are:

* [APC](http://www.php.net/manual/en/book.apc.php)
* Filesystem
* [Memcached](http://memcached.org/) (``php_memcache``, ``php_memcached`` and a
  PHP-only implementation for memcached with authentification via SASL)
* [MariaDB & MySQL](https://mariadb.org/)
* [Redis](http://redis.io/) (requires the pure PHP ``predis/prdis`` library)
* [SQLite](http://www.sqlite.org/)
* [XCache](http://xcache.lighttpd.net/)
* [ZendServer](http://files.zend.com/help/Zend-Platform/zend_cache_api.htm)

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
``composer update`` to install BabelCache.

In most cases, you will want to use ``wv\BabelCache\Factory`` to build new
cache instances. In order to do so, you have to extend it and implement the
provided abstract methods (which control stuff like the Memcached servers or
the PDO connection). See the ``tests/lib/TestFactory.php`` for a minimal
example.

Of course you can also instantiate all classes on your own, if you need to.

## Overview

BabelCache is divided into three parts:

### Adapters

Adapters are the basic building block of BabelCache. There is one adapter per
technology (APC, Memcached, Redis, ...) and each of them just implements a
very basic **key-value interface** (``set``, ``get``, ``remove``, ``exists``,
``clear``). Their interface is very similar to the one in the PSR proposal.

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
grouped by namespaces and key. This allows for partial flushes.

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

Additionally, there are more high-level caches, like the ``Expiring`` cache,
which transparently encodes an expiry into the cache values. The ``Cascade``
cache combines two other caches (one fast and one slow one).

Constructing caches is as easy as constructing adapters:

    ::::php

    <?php

    $factory = new MyFactory();                  // extends wv\BabelCache\Factory
    $adapter = $factory->getCache('apc');        // returns a Generic instance wrapping the APC adapter
    $adapter = $factory->getCache('filesystem'); // returns a FilesystemCache instance

    $adapter->set('my.awesome.namespace', 'key', 'value');

    print $adapter->get('my.awesome.namespace', 'key'); // prints 'value'

### PSR Wrapper

BabelCache does not follow the PSR proposal perfectly. It skips the
``CacheItem`` objects and handles the raw cache data. We believe the way to
encapsulate cache data in objects is a needless overhead.

However, to use BabelCache in a PSR world, you can optionally wrap BabelCache
to make it PSR compliant. For this, the implementations in ``wv\BabelCache\Psr``
are used.

    ::::php

    <?php

    $factory = new MyFactory();
    $adapter = $factory->getPsrCache('apc');  // returns a Psr\Cache instance wrapping the APC adapter

    $adapter->set('key', 'value');

    $item = $adapter->get('key'); // returns a CacheItem
    print $item->getValue();

**Note:** The PSR proposal is still just a proposal, so there are no official
interfaces yet. Until those are available, you have to provide them yourself,
as BabelCache only contains their implementations.

License
-------

BabelCache is licensed under the MIT License - see the LICENSE file for details.