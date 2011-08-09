<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * Alternative PHP Cache
 *
 * APC is a PECL module featuring both opcode and vardata caching. This class
 * wraps its functionality and already handles apc_exists() (added in APC 3.1.4).
 *
 * On APC < 3.1.4, all data will be manually serialized by this class, else
 * it will rely on APC to handle complex data.
 *
 * @author Christoph Mewes
 * @see    http://php.net/manual/de/book.apc.php
 */
class BabelCache_APC extends BabelCache_Abstract {
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

	public function getMaxKeyLength() {
		return 200; // unbekannt -> Schätzwert
	}

	public function hasLocking() {
		return true;
	}

	protected function _getRaw($key) {
		return apc_fetch($key);
	}

	protected function _get($key) {
		$value = apc_fetch($key);
		return $this->hasExistsMethod ? $value : unserialize($value);
	}

	protected function _setRaw($key, $value, $expiration) {
		$this->_delete($key); // explicit delete since APC does not allow multiple store() calls during the same request
		return apc_store($key, $value, $expiration);
	}

	protected function _set($key, $value, $expiration) {
		if (!$this->hasExistsMethod) $value = serialize($value);

		$this->_delete($key); // explicit delete since APC does not allow multiple store() calls during the same request
		return apc_store($key, $value, $expiration);
	}

	protected function _delete($key) {
		return apc_delete($key);
	}

	protected function _isset($key) {
		if ($this->hasExistsMethod) return apc_exists($key);
		return apc_fetch($key) !== false;
	}

	protected function _increment($key) {
		return apc_inc($key) !== false;
	}

	/**
	 * Creates a lock
	 *
	 * This method will use apc_add() to create a lock.
	 *
	 * @param  string $key  the key to lock
	 * @return boolean      true if successful, else false
	 */
	protected function _lock($key) {
		return apc_add($key, 1);
	}

	/**
	 * Releases a lock
	 *
	 * This method will use apc_delete() to remove a lock.
	 *
	 * @param  string $key  the key to unlock
	 * @return boolean      true if successful, else false
	 */
	protected function _unlock($key) {
		return apc_delete($key);
	}
}
