<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace wv\BabelCache\Adapter;

use wv\BabelCache\AdapterInterface;
use wv\BabelCache\Factory;
use wv\BabelCache\IncrementInterface;

/**
 * XCache
 *
 * This class wraps the XCache extension, which provides both opcode and vardata
 * caching.
 *
 * @see     http://xcache.lighttpd.net/
 * @package BabelCache.Adapter
 */
class XCache implements AdapterInterface, IncrementInterface {
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
		// XCache sucks: It's not available in CLI, but leaves the cache API available...
		if (PHP_SAPI === 'cli') return false;

		// XCache will throw a warning if it is misconfigured. We don't want to see that one.
		return function_exists('xcache_set') && @xcache_set('test', 1, 1);
	}

	/**
	 * Gets a value out of the cache
	 *
	 * This method will try to read the value from the cache.
	 *
	 * @param  string  $key    the object key
	 * @param  boolean $found  will be set to true or false when the method is finished
	 * @return mixed           the found value or null
	 */
	public function get($key, &$found = null) {
		$found = xcache_isset($key);
		if (!$found) return null;

		$value = xcache_get($key);

		return (is_int($value) || ctype_digit($value)) ? (int) $value : unserialize($value);
	}

	/**
	 * Sets a value
	 *
	 * This method will put a value into the cache. If it already exists, it
	 * will be overwritten.
	 *
	 * @param  string $key    the object key
	 * @param  mixed  $value  the value to store
	 * @return boolean        true on success, else false
	 */
	public function set($key, $value, $expiration = null) {
		// store integers as plain values, so we can easily increment them.
		$value = is_int($value) ? $value : serialize($value);

		return xcache_set($key, serialize($value), $expiration);
	}

	/**
	 * Deletes a single value from the cache
	 *
	 * @param  string $key  the object key
	 * @return boolean      true if the value was deleted, else false
	 */
	public function delete($key) {
		return xcache_unset($key);
	}

	/**
	 * Checks whether a value exists
	 *
	 * @param  string $key  the object key
	 * @return boolean      true if the value exists, else false
	 */
	public function exists($key) {
		return xcache_isset($key);
	}

	/**
	 * Deletes all values
	 *
	 * @return boolean  true if the flush was successful, else false
	 */
	public function clear() {
		xcache_clear_cache(XC_TYPE_PHP, 0);

		return true;
	}

	/**
	 * Increment a value
	 *
	 * This performs an atomic increment operation on the given key.
	 *
	 * @param  string $key  the key
	 * @return int          the value after it has been incremented or false if the operation failed
	 */
	public function increment($key) {
		return xcache_inc($key);
	}
}
