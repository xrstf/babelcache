BabelCache
==========

.. toctree::
  :hidden:

  example
  versioning
  faq

BabelCache is a PHP library that offers a simple wrapper for a number of
different caching systems, including:

* `APC`_
* `eAccelerator`_
* `Memcached`_ (both `php_memcache`_ and `php_memcached`_)
* `XCache`_
* `Zend Server`_
* File system (should only be used when no shared memory cache is available)

.. _APC:           http://pecl.php.net/package/APC
.. _XCache:        http://xcache.lighttpd.net/
.. _eAccelerator:  http://eaccelerator.net/
.. _Zend Server:   http://www.zend.com/en/products/server/
.. _Memcached:     http://memcached.org/
.. _php_memcache:  http://php.net/manual/en/book.memcache.php
.. _php_memcached: http://php.net/manual/en/book.memcached.php

This enables you to build an application without knowing what caching system
will be available on the target machine. You only use the generic interface and
can rely on BabelCache to do the rest.

Requirements
------------

BabelCache requires PHP >= 5.1. The classes can be loaded via any generic
autoloader or by using the bundled :file:`Autoload.php`, which will register a
simple SPL autoloader.

See the :doc:`faq` for some answers to common problems.

Download
--------

The current version can be downloaded from `Bitbucket`_. That's also where the
development happens. Bitbucket automatically generates downloadable ZIP
archives, so don't look for explicitely created downloads.

The latest version is always the `tip revision`_.

.. _Bitbucket:    https://bitbucket.org/webvariants/babelcache/
.. _tip revision: https://bitbucket.org/webvariants/babelcache/get/tip.zip

Namespacing
-----------

One of the greatest features of BabelCache is its ability to use namespaces in
systems that only offer plain key/value storage. For this to work, a
:doc:`versioning concept <versioning>` is used, which works transparently in the
background for you.

As a result, it's always possible to flush part of your cache (in contrast to
removing everything). A simple demo will show the possibilities:

.. sourcecode:: php

  <?php

  $factory = new MyCacheFactory();
  $cache   = $factory->getCache('BabelCache_XCache');

  // set some values

  $cache->set('namespace',           'key',  'value');
  $cache->set('namespace.sub',       'key2', true);
  $cache->set('namespace.sub',       'key3', 3.14);
  $cache->set('namespace.sub.subby', 'key',  null);

  // clear part of the cache

  $cache->flush('namespace.sub');

  // use the upper namespaces

  $cache->get('namespace', 'key');              // 'value'
  $cache->exists('namespace.sub.subby', 'key'); // false

See the :doc:`example file <example>` for a more complete demonstration.

API documentation
-----------------

Some automatically `generated documentation <coco/index.html>`_ for the PHP code
is available.
