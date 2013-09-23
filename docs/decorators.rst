Decorators
==========

Decorators allow you to transparently add filters and processing instructions to
your cache, like adding expiries or compressing the content. To use a decorator,
just apply it to your cache instance. You can mix decorators, if you need to.

.. sourcecode:: php

   <?php

   $factory = new wv\BabelCache\SimpleFactory();
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
