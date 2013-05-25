<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace wv\BabelCache\Psr;

use Psr\Cache\CacheInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheMultipleInterface;
use wv\BabelCache\Adapter\AdapterInterface;

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
class Cache implements CacheInterface, CacheMultipleInterface {
	protected $adapter;

	public function __construct(AdapterInterface $adapter) {
		$this->adapter = $adapter;
	}

	public function get($key) {
		$found = false;
		$value = $this->adapter->get($key, $found);

		return new CacheItem($key, $found ? $value : null, $found);
	}

	public function set($key, $value, $ttl = null) {
		$this->adapter->set($key, $value, $ttl);

		return true;
	}

	public function remove($key) {
		$this->adapter->remove($key, $value, $ttl);

		return true;
	}

	public function getMultiple($keys) {
		$result = array();

		// extremely simple solution, this can be improved by extending the adapter interfaces sometime
		foreach ($keys as $key) {
			$result[$key] = $this->get($key);
		}

		return $result;
	}

	public function setMultiple($items, $ttl = null) {
		foreach ($items as $item) {
			$this->set($item->getKey(), $item->getValue(), $ttl);
		}

		return true;
	}

	public function removeMultiple($keys) {
		$result = array();

		// extremely simple solution, this can be improved by extending the adapter interfaces sometime
		foreach ($keys as $key) {
			$result[$key] = $this->remove($key);
		}

		return $result;
	}

	public function clear() {
		$this->adapter->clear();

		return true;
	}
}
