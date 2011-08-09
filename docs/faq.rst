Frequently Asked Questions
==========================

What cache should I chose?
--------------------------

In most cases, **XCache** is a really great choice. We've experienced very
little problems with it (compared to APC, which has been proven to be somewhat
unstable under Windows and has some pitfalls -- see below). Plus, it's fast and
caches your opcodes.

When using *Fast CGI*, be aware that the PHP processes **do not share the same
cache** and that their caches are thrown away when the PHP process is killed.
For those setups, **Memcached** is a good choice. It doesn't really matter what
particular extension (php_memcache or php_memcached) you use.

XCache does not appear to work
------------------------------

Make sure that you have not only enabled XCache itself, but also the vardata
cache. See the `XCache configuration`_ for a more complete list of available
options. For a single developer machine, these settings work fine:

.. _XCache configuration: http://xcache.lighttpd.net/wiki/XcacheIni

::

  xcache.var_size  = 32M
  xcache.var_count = 1
  xcache.var_slots = 8K

For servers, you will want to tweak the ``var_size``.

APC does not appear to work
---------------------------

There is a rather strange configuration for APC which will forbid your scripts
to set the same value twice. This makes it impossible to overwrite values.
BabelCache tries to work around this by deleting a key before attempting to set
it, but this strangely does not fully solve the problem.

You may want to take a look at the ``slam_defense`` option for APC (`Google`_
will help you here).

.. _Google: http://www.google.com/search?q=slam_defense

As long as there is no definite answer to this problem, we strongly discourage
the usage of APC (as a caching system) and recommend XCache (see first
question).

What's the memory cache useful for?
-----------------------------------

In most cases, you either disable caching completely (using the blackhole cache)
or use a "real" caching system (like XCache). The memory cache will store every
value only for the current request and is therefore only useful when you have to
compute the same value over and over again during a single request and do not
wish to cache it permanently.

We have yet to find a real usecase for it. So don't worry when it's useless to
you -- that's normal. ;-)
