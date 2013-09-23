Frequently Asked Questions
==========================

What cache should I chose?
--------------------------

In most cases, **APC** (or, with PHP 5.5+, APCu) is a really great choice.
We've experienced very little problems with it. Plus, it's fast and caches your
opcodes.

When using *Fast CGI*, be aware that the PHP processes **do not share the same
cache** and that their caches are thrown away when the PHP process is killed.
For those setups, **Memcached** or **MySQL** are good choices.

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

What's the memory cache useful for?
-----------------------------------

In most cases, you either disable caching completely (using the blackhole cache)
or use a "real" caching system (like XCache). The memory cache will store every
value only for the current request and is therefore only useful when you have to
compute the same value over and over again during a single request and do not
wish to cache it permanently.

We have yet to find a real usecase for it. So don't worry when it's useless to
you -- that's normal. ;-)

Isn't using MySQL to cache data counter-productive?
---------------------------------------------------

You're doing it wrong(tm). ;-) If you go ahead and cache your database queries,
then yes, using MySQL would be pointless (as MySQL already caches query
results). But in this case you should ask yourself, if you're caching the right
things. Databases are fast, even MySQL.

In general, try to cache *expensive* computations. Most of the everyday queries
do not fall in that category. If your query only takes 50ms, don't waste your
time trying to cache it.

On the other hand, MySQL can be a great choice when working in a distributed
environment. Memcached has proven to add some network latency when it's not
running on localhost, whereas MySQL connections work a lot faster. Additionally,
MySQL caching can take advantage of native namespacing and hence requires a lot
less roundtrips than Memcached.
