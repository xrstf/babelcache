PSR Wrapper
===========

BabelCache does not follow the PSR proposal. It skips the ``Item`` objects and
has all methods on the services rather than on the items. We believe the way to
encapsulate cache data in objects is a needless overhead.

However, to use BabelCache in a PSR world, you can optionally wrap BabelCache
to make it PSR compliant. For this, the implementations in ``wv\BabelCache\Psr``
are used.

.. sourcecode:: php

   <?php

   $factory = new wv\BabelCache\SimpleFactory();
   $pool    = $factory->getPsrPool('apc');  // returns a Psr\Generic\Pool instance wrapping the APC adapter

   $pool->getItem('mykey')->set('value');

   $item = $pool->getItem('mykey'); // returns an Item
   print $item->get();

.. note::

  The PSR proposal is still just a proposal, so there are no official
  interfaces yet. Until those are available, you have to provide them yourself,
  as BabelCache only contains their implementations.
