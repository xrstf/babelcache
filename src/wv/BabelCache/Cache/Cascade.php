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
 * Cache cascade
 *
 * This cache combines two other caches. The primary cache is supposed to be
 * fast, the secondary cache is supposed to be the slow one.
 *
 * @package BabelCache.Cache
 */
class Cascade implements CacheInterface {
	protected $primaryCache;
	protected $secondaryCache;

	public function __construct(CacheInterface $primaryCache, CacheInterface $secondaryCache) {
		$this->primaryCache   = $primaryCache;
		$this->secondaryCache = $secondaryCache;
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
		$value = $this->primaryCache->get($namespace, $key, $default, $found);

		if (!$found) {
			$value = $this->secondaryCache->get($namespace, $key, $default, $found);

			if ($found) {
				$this->primaryCache->set($namespace, $key, $value);
			}
		}

		return $value;
	}

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
		$this->primaryCache->set($namespace, $key, $value);
		$this->secondaryCache->set($namespace, $key, $value);

		return $value;
	}

	/**
	 * Removes a single value from the cache
	 *
	 * @param  string $namespace  the namespace to use
	 * @param  string $key        the object key
	 * @return boolean            true if the value was deleted, else false
	 */
	public function remove($namespace, $key) {
		$this->primaryCache->remove($namespace, $key);
		$this->secondaryCache->remove($namespace, $key);
	}

	/**
	 * Checks whether a value exists
	 *
	 * @param  string $namespace  the namespace to use
	 * @param  string $key        the object key
	 * @return boolean            true if the value exists, else false
	 */
	public function exists($namespace, $key) {
		return $this->primaryCache->exists($namespace, $key) || $this->secondaryCache->exists($namespace, $key);
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
		$this->primaryCache->clear($namespace, $recursive);
		$this->secondaryCache->clear($namespace, $recursive);
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
		return $this->primaryCache->lock($namespace, $key);
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
		return $this->primaryCache->unlock($namespace, $key);
	}

	/**
	 * Waits for a lock to be released
	 *
	 * This method will wait for a specific amount of time for the lock to be
	 * released. For this, it constantly checks the lock (tweak the check
	 * interval with the last parameter).
	 *
	 * When the maximum waiting time elapsed, the $default value will be
	 * returned. Else the value will be read from the cache.
	 *
	 * @param  string $namespace      the namespace
	 * @param  string $key            the key
	 * @param  mixed  $default        the value to return if the lock does not get released
	 * @param  int    $maxWaitTime    the maximum waiting time (in seconds)
	 * @param  int    $checkInterval  the check interval (in milliseconds)
	 * @return mixed                  the value from the cache if the lock was released, else $default
	 */
	public function waitForLockRelease($namespace, $key, $default = null, $maxWaitTime = 3, $checkInterval = 750) {
		return $this->primaryCache->waitForLockRelease($namespace, $key, $default, $maxWaitTime, $checkInterval);
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
		$this->primaryCache->setPrefix($prefix);
		$this->secondaryCache->setPrefix($prefix);
	}
}
