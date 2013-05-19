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

	public static function isAvailable() {
		static $avail = null;

		if ($avail === null) {
			if (!function_exists('apc_store')) {
				$avail = false;
			}
			else {
				apc_delete('test');
				$avail = apc_store('test', 1, 1);
			}
		}

		return $avail;
	}

	public function get($key, &$found = null) {
		$value = apc_fetch($key, $found);

		return $found ? unserialize($value) : null;
	}

	public function set($key, $value, $expiration = null) {
		// explicit delete since APC does not allow multiple store() calls during the same request
		$this->delete($key);

		return apc_store($key, serialize($value), $expiration);
	}

	public function remove($key) {
		return apc_delete($key);
	}

	public function exists($key) {
		if ($this->hasExistsMethod) {
			return apc_exists($key);
		}

		apc_fetch($key, $found);

		return $found;
	}

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
	 * This method will use apc_delete() to remove a lock.
	 *
	 * @param  string $key  the key to unlock
	 * @return boolean      true if successful, else false
	 */
	public function unlock($key) {
		return apc_delete('lock:'.$key);
	}
}
