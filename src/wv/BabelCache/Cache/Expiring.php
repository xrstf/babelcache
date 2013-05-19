<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace wv\BabelCache\Cache;

use wv\BabelCache\CacheInterface;

/**
 * Cache wrapper to generically handle timeouts
 *
 * Since not all implementations provide native support for timeouts, this class
 * puts a layer around another, real cache, adding and handling timeout
 * information transparently to all values.
 *
 * @package BabelCache.Cache
 */
class Expiring implements CacheInterface {
	protected $cache; ///< CacheInterface  the wrapped caching instance
	protected $ttl;

	const EXPIRE_KEY = '__expire__';
	const VALUE_KEY  = '__value__';

	/**
	 * Constructor
	 *
	 * @param CacheInterface $realCache  the caching instance to be wrapped
	 * @param int            $ttl        default ttl for all written items
	 */
	public function __construct(CacheInterface $realCache, $ttl) {
		$this->cache = $realCache;
		$this->ttl   = (int) $ttl;
	}

	/**
	 * Sets a value
	 *
	 * This method will put a value into the cache. If it already exists, it
	 * will be overwritten.
	 *
	 * @param  string $namespace  namespace to use
	 * @param  string $key        object key
	 * @param  mixed  $value      value to store
	 * @param  mixed  $ttl        timeout in seconds
	 * @return mixed              the set value
	 */
	public function set($namespace, $key, $value, $timeout = null) {
		$expire = $timeout === null ? (time() + $this->ttl) : $timeout;
		$data   = array(self::EXPIRE_KEY => $expire, self::VALUE_KEY => $value);

		return $this->cache->set($namespace, $key, $data);
	}

	/**
	 * Gets a value out of the cache
	 *
	 * This method will try to read the value from the cache. If it's not found,
	 * $default will be returned.
	 *
	 * @param  string $namespace  the namespace to use
	 * @param  string $key        the object key
	 * @param  mixed  $default    the default value
	 * @return mixed              the found value if not expired or $default
	 */
	public function get($namespace, $key, $default = null, &$found = null) {
		$found = false;
		$data  = $this->cache->get($namespace, $key, null, $found);

		if (!$found) {
			return $default;
		}

		$expired = isset($data[self::EXPIRE_KEY]) ? time() > $data[self::EXPIRE_KEY] : false;

		if ($expired) {
			return $default;
		}

		$found = true; // update the reference

		return isset($data[self::VALUE_KEY]) ? $data[self::VALUE_KEY] : $data;
	}

	public function remove($namespace, $key) {
		return $this->cache->remove($namespace, $key);
	}

	public function exists($namespace, $key) {
		return $this->cache->exists($namespace, $key);
	}

	public function clear($namespace, $recursive = false) {
		return $this->cache->clear($namespace, $recursive);
	}

	public function lock($namespace, $key) {
		return $this->cache->lock($namespace, $key);
	}

	public function unlock($namespace, $key) {
		return $this->cache->unlock($namespace, $key);
	}

	public function waitForLockRelease($namespace, $key, $default = null, $maxWaitTime = 3, $checkInterval = 750) {
		return $this->cache->waitForLockRelease($namespace, $key, $default, $maxWaitTime, $checkInterval);
	}
}
