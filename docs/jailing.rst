Jailing
=======

Often you will want to have a simple key-value cache for one of your components.
If so, you *could* just give them a cache adapter and be done. Unfortunately,
this will suck hard when it comes to clearing the cache (since "clear" in APC
actually means "wipe the whole userland cache"). Hence if one component clears
the cache, all other components are affected.

To make sure you can use multiple cache adapters to the same storage and still
have namespaced cache clearing, you can use the Jailed adapter. This adapter
simply jails a regular, full-blown cache to a fixed namespace and will only ever
clear that one.

.. sourcecode:: php

   <?php

   $factory = new wv\BabelCache\SimpleFactory();
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
