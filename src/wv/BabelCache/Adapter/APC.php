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
use wv\BabelCache\LockingInterface;

/**
 * Alternative PHP Cache
 *
 * APC is a PECL module featuring both opcode and vardata caching. This class
 * wraps its functionality and already handles apc_exists() (added in APC 3.1.4).
 *
 * On APC < 3.1.4, all data will be manually serialized by this class, else
 * it will rely on APC to handle complex data.
 *
 * @see     http://php.net/manual/de/book.apc.php
 * @package BabelCache.Adapter
 */
class APC implements AdapterInterface, LockingInterface {
	private $hasExistsMethod = null; ///< boolean  true if apc_exists() exists, else false

	/**
	 * Constructor
	 *
	 * Only checks for apc_exists().
	 */
	public function __construct() {
		$this->hasExistsMethod = function_exists('apc_exists');
	}

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
		static $avail = null;

		if ($avail === null) {
			// @codeCoverageIgnoreStart
			if (!function_exists('apc_store')) {
				$avail = false;
			}
			// @codeCoverageIgnoreEnd
			else {
				apc_delete('test');
				$avail = apc_store('test', 1, 1);
			}
		}

		return $avail;
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
		$value = apc_fetch($key, $found);

		return $found ? unserialize($value) : null;
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
		// explicit delete since APC does not allow multiple store() calls during the same request
		$this->delete($key);

		return apc_store($key, serialize($value), $expiration);
	}

	/**
	 * Deletes a single value from the cache
	 *
	 * @param  string $key  the object key
	 * @return boolean      true if the value was deleted, else false
	 */
	public function delete($key) {
		return apc_delete($key);
	}

	/**
	 * Checks whether a value exists
	 *
	 * @param  string $key  the object key
	 * @return boolean      true if the value exists, else false
	 */
	public function exists($key) {
		if ($this->hasExistsMethod) {
			return apc_exists($key);
		}

		// @codeCoverageIgnoreStart
		apc_fetch($key, $found);

		return $found;
		// @codeCoverageIgnoreEnd
	}

	/**
	 * Deletes all values
	 *
	 * @return boolean  true if the flush was successful, else false
	 */
	public function clear() {
		return apc_clear_cache('user');
	}

	/**
	 * Creates a lock
	 *
	 * This method will use apc_add() to create a lock.
	 *
	 * @param  string $key  the key to lock
	 * @return boolean      true if successful, else false
	 */
	public function lock($key) {
		return apc_add('lock:'.$key, 1);
	}

	/**
	 * Releases a lock
	 *
	 * This method will use apc_delete() to delete a lock.
	 *
	 * @param  string $key  the key to unlock
	 * @return boolean      true if successful, else false
	 */
	public function unlock($key) {
		return apc_delete('lock:'.$key);
	}

	/**
	 * Check if a key is locked
	 *
	 * @param  string $key  the key
	 * @return boolean      true if the key is locked, else false
	 */
	public function hasLock($key) {
		return $this->exists('lock:'.$key);
	}
}
