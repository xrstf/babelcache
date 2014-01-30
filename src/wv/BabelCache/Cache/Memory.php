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
use wv\BabelCache\Factory;
use wv\BabelCache\Util;

/**
 * Runtime cache
 *
 * @package BabelCache.Cache
 */
class Memory implements CacheInterface {
	protected $data   = array();  ///< array   contains the cached data {key: value, key: value}
	protected $locks  = array();  ///< array   contains the locked keys
	protected $prefix = '';       ///< string  cache key prefix

	/**
	 * Checks whether a caching system is avilable
	 *
	 * This method will be called before an instance is created. It is supposed
	 * to check for the required functions and whether user data caching is
	 * enabled.
	 *
	 * @param  Factory $factory  the project's factory to give the adapter some more knowledge
	 * @return boolean           true if the cache can be used, else false
	 */
	public static function isAvailable(Factory $factory = null) {
		return true;
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
		$this->data[$this->prefix][$namespace][$key] = $value;

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

		return $found ? $this->data[$this->prefix][$namespace][$key] : $default;
	}

	/**
	 * Deletes a single value from the cache
	 *
	 * @param  string $namespace  the namespace to use
	 * @param  string $key        the object key
	 * @return boolean            true if the value was deleted, else false
	 */
	public function delete($namespace, $key) {
		$exists = $this->exists($namespace, $key);
		unset($this->data[$this->prefix][$namespace][$key]);

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
		return isset($this->data[$this->prefix][$namespace]) && array_key_exists($key, $this->data[$this->prefix][$namespace]);
	}

	/**
	 * Deletes all values in a given namespace
	 *
	 * @param  string  $namespace  the namespace to use
	 * @param  boolean $recursive  if set to true, all child namespaces will be cleared as well
	 * @return boolean             true if the flush was successful, else false
	 */
	public function clear($namespace, $recursive = false) {
		Util::checkString($namespace, 'namespace');

		$this->data  = $this->clearArray($this->data, $namespace, $recursive);
		$this->locks = array();

		return true;
	}

	protected function clearArray($array, $namespace, $recursive) {
		unset($array[$this->prefix][$namespace]);

		if (!$recursive) {
			return $array;
		}

		if (!isset($array[$this->prefix])) {
			return $array;
		}

		$pattern    = "$namespace.*";
		$namespaces = array_keys($array[$this->prefix]);

		foreach ($namespaces as $pkg) {
			if (fnmatch($pattern, $pkg)) {
				unset($array[$this->prefix][$pkg]);
			}
		}

		return $array;
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
		if (isset($this->locks[$this->prefix][$namespace][$key])) return false;
		$this->locks[$this->prefix][$namespace][$key] = 1;

		return true;
	}

	/**
	 * Releases a lock
	 *
	 * This method will delete a lock for a specific key.
	 *
	 * @param  string $namespace  the namespace
	 * @param  string $key        the key
	 * @return boolean            true if the lock was released, else false
	 */
	public function unlock($namespace, $key) {
		if (!isset($this->locks[$this->prefix][$namespace][$key])) return false;
		unset($this->locks[$this->prefix][$namespace][$key]);

		return true;
	}

	/**
	 * Check if a key is locked
	 *
	 * @param  string $namespace  the namespace
	 * @param  string $key        the key
	 * @return boolean            true if the key is locked, else false
	 */
	public function hasLock($namespace, $key) {
		return isset($this->locks[$this->prefix][$namespace][$key]);
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
		$this->prefix = trim($prefix);
	}
}
