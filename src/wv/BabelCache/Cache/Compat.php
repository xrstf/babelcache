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
 * Compatibility layer
 *
 * Wrap a BabelCache 2.x cache in this decorator to get the old 1.x methods
 * back.
 *
 * @package BabelCache.Cache
 */
class Compat implements CacheInterface {
	protected $cache; ///< CacheInterface  the wrapped caching instance

	/**
	 * Constructor
	 *
	 * @param CacheInterface $realCache  the caching instance to be wrapped
	 */
	public function __construct(CacheInterface $realCache) {
		$this->cache = $realCache;
	}

	/* 1.x interface */

	public function delete($namespace, $key) {
		return $this->cache->remove($namespace, $key);
	}

	public function flush($namespace, $recursive = false) {
		return $this->cache->clear($namespace, $recursive);
	}

	public function waitForObject($namespace, $key, $default = null, $maxWaitTime = 3, $checkInterval = 50) {
		return Util::waitForLockRelease($this->cache, $namespace, $key, $default, $maxWaitTime, $checkInterval);
	}

	/* 1:1 proxies for the 2.x interface */

	/**
	 * Sets a value
	 *
	 * This method will put a value into the cache. If it already exists, it
	 * will be overwritten.
	 *
	 * @param  string $namespace  the namespace to use
	 * @param  string $key        the object key
	 * @param  mixed  $value      the value to store
	 * @return mixed              the set value
	 */
	public function set($namespace, $key, $value) {
		return $this->cache->set($namespace, $key, $value);
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
	 * @return mixed              the found value or $default
	 */
	public function get($namespace, $key, $default = null, &$found = null) {
		return $this->cache->get($namespace, $key, $default, $found);
	}

	/**
	 * Removes a single value from the cache
	 *
	 * @param  string $namespace  the namespace to use
	 * @param  string $key        the object key
	 * @return boolean            true if the value was deleted, else false
	 */
	public function remove($namespace, $key) {
		return $this->cache->remove($namespace, $key);
	}

	/**
	 * Checks whether a value exists
	 *
	 * @param  string $namespace  the namespace to use
	 * @param  string $key        the object key
	 * @return boolean            true if the value exists, else false
	 */
	public function exists($namespace, $key) {
		return $this->cache->exists($namespace, $key);
	}

	/**
	 * Removes all values in a given namespace
	 *
	 * This method will remove all values by making them unavailable. For this,
	 * the version number of the flushed namespace is increased by one.
	 *
	 * Implementations are *not* required to support non-recursive flushes. If
	 * those are not supported, a recursive flush must be performed instead.
	 * Userland code should assume that every clear operation is recursive and
	 * the $recursive flag is a mere optimization hint.
	 *
	 * @param  string  $namespace  the namespace to use
	 * @param  boolean $recursive  if set to true, all child namespaces will be cleared as well
	 * @return boolean             true if the flush was successful, else false
	 */
	public function clear($namespace, $recursive = false) {
		return $this->cache->clear($namespace, $recursive);
	}

	/**
	 * Locks a key
	 *
	 * This method will create a lock for a specific key. Caches that don't
	 * support native locking will use a special "lock:key" value.
	 *
	 * @param  string $namespace  the namespace
	 * @param  string $key        the key
	 * @return boolean            true if the lock was aquired, else false
	 */
	public function lock($namespace, $key) {
		return $this->cache->lock($namespace, $key);
	}

	/**
	 * Releases a lock
	 *
	 * This method will remove a lock for a specific key.
	 *
	 * @param  string $namespace  the namespace
	 * @param  string $key        the key
	 * @return boolean            true if the lock was released, else false
	 */
	public function unlock($namespace, $key) {
		return $this->cache->unlock($namespace, $key);
	}

	/**
	 * Check if a key is locked
	 *
	 * @param  string $namespace  the namespace
	 * @param  string $key        the key
	 * @return boolean            true if the key is locked, else false
	 */
	public function hasLock($namespace, $key) {
		return $this->cache->hasLock($namespace, $key);
	}

	/**
	 * Sets the key prefix
	 *
	 * The key prefix will be put in front of the generated cache key, so that
	 * multiple installations of the same system can co-exist on the same
	 * machine.
	 *
	 * @param string $prefix  the prefix to use (will be trimmed)
	 */
	public function setPrefix($prefix) {
		return $this->cache->setPrefix($prefix);
	}
}
