BabelCache
==========

.. toctree::
  :hidden:

  versioning
  psr
  decorators
  jailing
  faq

BabelCache is a feature-complete caching library for PHP 5.3+. It supports a
wide range of adapters, namespaced caching and provides an experimental
support for the `PSR Cache Proposal`_.

You can store arbitrary elements (with the exception of resources and Closures).
BabelCache will always respect their types, so that when you store an int, you
will get an int back.

Supported caching backends are:

* `APC`_
* Blackhole (useful for transparently disabling caching)
* Filesystem
* `Memcached`_ (`php_memcache`_, `php_memcached`_ and a PHP-only implementation
  for memcached with authentification via SASL)
* `MariaDB & MySQL`_
* Memory (runtime caching only)
* `Redis`_ (requires the pure PHP ``predis/prdis`` library)
* `SQLite`_
* `WinCache`_
* `XCache`_
* `ZendServer`_

An automatically `generated documentation <api/index.html>`_ for the PHP code
is available.

.. _PSR Cache Proposal: https://github.com/php-fig/fig-standards/pull/96
.. _APC:                http://www.php.net/manual/en/book.apc.php
.. _MariaDB & MySQL:    https://mariadb.org/
.. _Memcached:          http://memcached.org/
.. _php_memcache:       http://php.net/manual/en/book.memcache.php
.. _php_memcached:      http://php.net/manual/en/book.memcached.php
.. _Redis:              http://redis.io/
.. _SQLite:             http://www.sqlite.org/
.. _WinCache:           http://www.iis.net/downloads/microsoft/wincache-extension
.. _XCache:             http://xcache.lighttpd.net/
.. _ZendServer:         http://files.zend.com/help/Zend-Platform/zend_cache_api.htm

Installation
------------

Add the following requirements to your :file:`composer.json`:

.. sourcecode:: javascript

   {
       "require": {
           "webvariants/babelcache": "$VERSION"
       }
   }

Replace ``$VERSION`` with one of the available versions on `Packagist`_. Use
``composer update`` to install BabelCache and the Composer autoloader to load
it.

.. _Packagist: https://packagist.org/packages/webvariants/babelcache

Usage
-----

In most cases, you will want to use the factory to create the caching adapter
for you. You can either use the prepared ``wv\BabelCache\SimpleFactory`` or, if
you need more control, extend the ``wv\BabelCache\Factory``.

.. sourcecode:: php

   <?php

   $factory = new wv\BabelCache\SimpleFactory();
   $adapter = $factory->getAdapter('apc');

   $adapter->set('world', 'dominated');

Of course you can also instantiate all classes on your own, if you need to.

See the :doc:`faq` for some answers to common problems.

Overview
--------

BabelCache is divided into two main parts, adapters and caches. Additionally, it
provides a number of other convenience stuff like decorators.

Adapters
""""""""

Adapters are the basic building block of BabelCache. There is one adapter per
technology (APC, Memcached, Redis, ...) and each of them just implements a
very basic **key-value interface** (``set``, ``get``, ``delete``, ``exists``,
``clear``).

You are free to use the adapters and hence a flat key-value storage, if that
suits your needs. For example:

.. sourcecode:: php

   <?php

   $factory = new wv\BabelCache\SimpleFactory();
   $adapter = $factory->getAdapter('apc');

   $adapter->set('key', 'value');

   print $adapter->get('key'); // prints 'value'


Caches
""""""

A cache implements the advanced **namespace interface**, where elements are
grouped by namespaces and key. This allows for partial flushes. See the
:doc:`full description <versioning>` for the advantages and drawbacks to this.

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

.. sourcecode:: php

   <?php

   $factory = new wv\BabelCache\SimpleFactory();

   // For the sake of this example, we want to create a filesystem cache. This
   // requires a defined cache directory.
   $factory->setCacheDirectory('/tmp/cache');

   $adapter = $factory->getCache('apc');        // returns a Generic instance wrapping the APC adapter
   $adapter = $factory->getCache('filesystem'); // returns a FilesystemCache instance

   $adapter->set('my.awesome.namespace', 'key', 'value');

   print $adapter->get('my.awesome.namespace', 'key'); // prints 'value'

Advanced
--------

Besides the very basic caching classes, BabelCache contains more helpers to aid
in integrating it into projects and make it even easier to use.

* The :doc:`PSR Wrapper <psr>` allows you to easily use BabelCache as a drop-in
  solution for a wide range of frameworks.
* :doc:`Decorators <decorators>` transparently add features like expiries or
  compression.
* Via :doc:`Jailing <jailing>` you can create distinct caches and still provide
  a simpley key-value interface.
