<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace wv\BabelCache\Psr\Generic;

use Psr\Cache\PoolInterface;
use Psr\Cache\ItemInterface;
use wv\BabelCache\Adapter\AdapterInterface;
use wv\BabelCache\Psr\BrokenKeyException;

/**
 * Wrapper around BabelCache's interface to be PSR compliant
 *
 * Use this if you really need to and can afford the overhead of wrapping all
 * your data in CacheItem instances.
 *
 * By design, this implementation does not support any kind of namespacing.
 *
 * @package BabelCache.Psr
 */
class Pool implements PoolInterface {
	protected $adapter;

	public function __construct(AdapterInterface $adapter) {
		$this->adapter = $adapter;
	}

	/**
	 * Returns a Cache Item representing the specified key.
	 *
	 * This method must always return an ItemInterface object, even in case of
	 * a cache miss.
	 *
	 * @throws InvalidArgumentException  in case of an invalid key
	 * @param  string $key               the key for which to return the corresponding Cache Item.
	 * @return ItemInterface             the corresponding Cache Item.
	 */
	public function getItem($key) {
		$this->checkKey($key);

		$found = false;
		$item  = $this->adapter->get($key, $found);

		if (!$found) {
			$item = array('payload' => null, 'ttl' => null);
		}

		return new CacheItem($key, $item, $found, $this);
	}

	/**
	 * Returns a traversable set of cache items.
	 *
	 * @param  array $keys   an indexed array of keys of items to retrieve.
	 * @return \Traversable  a traversable collection of Cache Items in the same
	 *                       order as the $keys parameter, keyed by the cache keys
	 *                       of each item. If no items are found an empty
	 *                       Traversable collection will be returned.
	 */
	public function getItems($keys) {
		$result = array();

		// extremely simple solution, this can be improved by extending the adapter interfaces sometime
		foreach ($keys as $key) {
			$this->checkKey($key);
			$result[$key] = $this->get($key);
		}

		return $result;
	}

	/**
	 * Deletes all items in the pool.
	 *
	 * @return PoolInterface  reference to self
	 */
	public function clear() {
		$this->adapter->clear();

		return $this;
	}

	/**
	 * Checks if a key is syntactically valid
	 *
	 * @param  string $key         the cache key
	 * @throws BrokenKeyException  if there are forbidden characters
	 */
	protected function checkKey($key) {
		if (!preg_match('#^[^{}()/\\@:]+$#', $key)) {
			throw new BrokenKeyException('Invalid cache key given!');
		}
	}
}
