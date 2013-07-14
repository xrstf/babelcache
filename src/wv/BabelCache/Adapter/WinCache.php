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
use wv\BabelCache\LockingInterface;

/**
 * WinCache
 *
 * This class wraps the WinCache extension, which provides both opcode and
 * userland caching.
 *
 * @see     http://www.iis.net/downloads/microsoft/wincache-extension
 * @package BabelCache.Adapter
 */
class WinCache implements AdapterInterface, IncrementInterface, LockingInterface {
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
		return function_exists('wincache_ucache_get');
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
		$value = wincache_ucache_get($key, $found);

		return $found ? $value : null;
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
	public function set($key, $value, $ttl = 0) {
		return wincache_ucache_set($key, $value, $ttl);
	}

	/**
	 * Deletes a single value from the cache
	 *
	 * @param  string $key  the object key
	 * @return boolean      true if the value was deleted, else false
	 */
	public function delete($key) {
		return wincache_ucache_delete($key);
	}

	/**
	 * Checks whether a value exists
	 *
	 * @param  string $key  the object key
	 * @return boolean      true if the value exists, else false
	 */
	public function exists($key) {
		return wincache_ucache_exists($key);
	}

	/**
	 * Deletes all values
	 *
	 * @return boolean  true if the flush was successful, else false
	 */
	public function clear() {
		return wincache_ucache_clear();
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
		return wincache_ucache_inc($key);
	}

	/**
	 * Locks a key
	 *
	 * This method will create a lock for a specific key.
	 *
	 * @param  string $key  the key
	 * @return boolean      true if the lock was aquired, else false
	 */
	public function lock($key) {
		return wincache_ucache_add('lock:'.$key, 1);
	}

	/**
	 * Releases a lock
	 *
	 * This method will delete a lock for a specific key.
	 *
	 * @param  string $key  the key
	 * @return boolean      true if the lock was released or there was no lock, else false
	 */
	public function unlock($key) {
		return wincache_ucache_delete('lock:'.$key);
	}

	/**
	 * Check if a key is locked
	 *
	 * @param  string $key  the key
	 * @return boolean      true if the key is locked, else false
	 */
	public function hasLock($key) {
		return wincache_ucache_exists('lock:'.$key);
	}
}
