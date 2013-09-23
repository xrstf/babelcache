Versioning
==========

.. note::

  This does not apply to caches having native support for namespacing, like the
  filesystem, memory or MySQL caches.

Most in-memory caches only allow the user to store simple key/value pairs. For
small applications that cache only a few values, this is acceptable. But when
the system grows, it can become very expensive to clear the whole cache when
one element has changed.

BabelCache uses the idea outlined in the `Memcache Wiki`_ to tackle this
problem:

  Assign every namespace an internal version number and only increment this
  number when the user flushes the cache. This does **not** remove the data,
  makes it unavailable.

.. _Memcache Wiki: http://code.google.com/p/memcached/wiki/FAQ#Deleting_by_Namespace

Example
-------

An example should outline the effects and possibilities of this concept:

.. sourcecode:: php

  <?php

  $factory = new wv\BabelCache\SimpleFactory();
  $cache   = $factory->getCache('apc');         // note that APC does not support namespacing itself

  // set three values
  $cache->set('my.namespace', 'mykey',      42);
  $cache->set('my.namespace', 'anotherkey', 23);
  $cache->set('my',           'muh',        3.14);

  // getting them back
  $cache->get('my', 'muh'); // 3.14

  // delete elements
  $cache->delete('my', 'muh');

  $cache->get('my', 'muh');              // null
  $cache->get('my', 'muh', 'mydefault'); // 'mydefault'

  // re-add
  $cache->set('my', 'muh', 3.14);

  // flush partially
  $cache->flush('my.namespace'); // mykey and anotherkey become unavailable
  $cache->get('my', 'muh');      // still 3.14

  // re-add
  $cache->set('my.namespace', 'mykey',      42);
  $cache->set('my.namespace', 'anotherkey', 23);
  $cache->set('my',           'muh',        3.14);

  // flush partially
  $cache->flush('my');
  $cache->exists('my', 'muh'); // false

Algorithm
---------

Let's see how this works:

Every namespace consists of one or more parts, separated by dots:
``my.super.namespace``. When BabelCache generates the final key to use for
storing the data, it splits the namespace up into its parts. Then, beginning by
the first, it looks for the **current** versions of the namespaces.

So in the first step, the version for ``my`` is searched. Versions are stored
like normal values in the cache, but are prefixed with ``version/``. We look for
``version/my``, to be exact. If the version is found, it is attached to the
namespace, else a fresh one is generated (and stored). Let's suppose we found a
version for ``my`` and that it is ``1234``. The namespace now looks like
``my@1234``.

The next step is to look for the version of the following namespaces. That's
where the magic happens: We don't look for ``version/my.super``, but for
``version/my@1234.super``. This shows that the version of any sub-namespace is
directly related to its parents namespaces.

This loop goes on until we have constructed the complete cache key:
``my@1234.super@5.namespace@842`` (example). In the final step, the element key
is attached to the string, resulting in ``my@1234.super@5.namespace@842$key``.

Flushing
--------

Now let's see how a ``flush()`` works.

Flushing means nothing more than incremeting the version number of a specific
namespace. If we take the key from the example above and try to flush
``my.super`` (making all data in ``my.super`` and ``my.super.namespace``
unavailable), the flush method will get the current version (5) and just
increment it.

After this, if we try to access ``my.super.namespace$key`` again, we can't find
it. When we perform the same algorithm as outlined above, we won't find the
version for ``my@1234.super@6.namespace``. Since we can't find it, a fresh
version is generated and used from then on: ``my@1234.super@6.namespace@1`` (for
example).

Drawbacks
---------

The major drawback of this technique is, that flushes are **always recursive**.
The re-assignment of a new version in the middle of the cache key makes all data
in deeper namespaces unavailable.

This can be a problem if you're using really, really deep namespaces with very
expensive to create objects. In that case you might want to flatten your
structure a bit. In most cases, a flat structure combined with more than one
flush can help improving the performance.

Also, the maximum length of cache keys (combination of namespace and key) is
reduced, as there needs to be enough space to include the version numbers.

Performance
-----------

As we can see, ``flush()``\ing is a really simple operation. No data is touched,
only one version has to be incremented. The drawback of course being that the
flush is always recursive and you may lose too much data.

Another thing should be mentioned: Since no data is really removed, your cache
will fill up until it reaches its maximum size. Old elements will then be
garbage collected. This is normal behaviour for all in-memory caches and will
not result in a slowdown.
