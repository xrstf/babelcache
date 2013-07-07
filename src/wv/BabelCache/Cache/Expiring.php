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
	public function set($namespace, $key, $value, $ttl = null) {
		$expire = time() + ($ttl === null ? $this->ttl : $ttl);
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
		$this->cache->setPrefix($prefix);
	}
}
