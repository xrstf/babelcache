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
 * Runtime cache
 *
 * @package BabelCache.Cache
 */
class Memory implements CacheInterface {
	protected $data = array();  ///< array  contains the cached data {key: value, key: value}

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
		$this->data[$namespace][$key] = $value;

		return $value;
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
		$found = $this->exists($namespace, $key);

		return $found ? $this->data[$namespace][$key] : $default;
	}

	/**
	 * Removes a single value from the cache
	 *
	 * @param  string $namespace  the namespace to use
	 * @param  string $key        the object key
	 * @return boolean            true if the value was deleted, else false
	 */
	public function remove($namespace, $key) {
		$exists = $this->exists($namespace, $key);
		unset($this->data[$namespace][$key]);

		return $exists;
	}

	/**
	 * Checks whether a value exists
	 *
	 * @param  string $namespace  the namespace to use
	 * @param  string $key        the object key
	 * @return boolean            true if the value exists, else false
	 */
	public function exists($namespace, $key) {
		return isset($this->data[$namespace]) && array_key_exists($key, $this->data[$namespace]);
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
		if (empty($this->data)) {
			return true;
		}

		unset($this->data[$namespace]);

		if (!$recursive) {
			return true;
		}

		$pattern    = "$namespace*";
		$namespaces = array_keys($this->data);

		foreach ($namespaces as $pkg) {
			if (fnmatch($pattern, $pkg)) {
				unset($this->data[$pkg]);
			}
		}

		return true;
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
		return true;
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
		return true;
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
		return $this->get($namespace, $key, $default);
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
		// do nothing
	}
}
