BabelCache is a PHP library that offers a simple wrapper for a number of
different caching systems, including:

- Filesystem (fallback)
- APC
- XCache
- eAccelerator
- Zend Server
- Memcache (both php_memcache and php_memcached)

This enables you to build an application without knowing what caching system
will be available on the target machine. You only use the generic interface and
can rely on BabelCache to do the rest.

### Requirements

BabelCache requires PHP >= 5.1. The classes can be loaded via any generic
autoloader or by using the bundled `Autoload.php`, which will register a simple
SPL autoloader.

### Namespacing

One of the greatest features of BabelCache is its ability to use namespaces in
systems that only offer plain key/value storage. For this to work, a [versioning
concept](versioning.html) is used, which works transparently in the background
for you.

As a result, it's always possible to flush part of your cache (in contrast to
removing everything). A simple demo will show the possibilities:

~~~php~~~
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
~~~

See the [example file](example.html) for a more complete demonstration.

### Overview
