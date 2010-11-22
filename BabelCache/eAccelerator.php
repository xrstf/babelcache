<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * eAccelerator
 *
 * eAccelerator is a free open-source PHP accelerator & optimizer. Be aware that
 * in recent versions (> 0.9.5), you have to enable the vardata functions (put,
 * get, ...) via the compile switch --with-eaccelerator-shared-memory.
 *
 * @author Christoph Mewes
 * @see    http://eaccelerator.net/
 */
class BabelCache_eAccelerator extends BabelCache_Abstract {
	public function getMaxKeyLength() {
		return 200; // unbekannt -> Schätzwert
	}

	public function hasLocking() {
		return true;
	}

	public static function isAvailable() {
		// Wir müssen auch prüfen, ob Werte gespeichert werden können (oder ob nur der Opcode-Cache aktiviert ist).
		return function_exists('eaccelerator_put') && eaccelerator_put('test', 1, 1);
	}

	protected function _getRaw($key) {
		return eaccelerator_get($key);
	}

	protected function _get($key) {
		return unserialize(eaccelerator_get($key));
	}

	protected function _setRaw($key, $value, $expiration) {
		return eaccelerator_put($key, $value, $expiration);
	}

	protected function _set($key, $value, $expiration) {
		return eaccelerator_put($key, serialize($value), $expiration);
	}

	protected function _delete($key) {
		return eaccelerator_rm($key);
	}

	protected function _isset($key) {
		return eaccelerator_get($key) !== null;
	}

	/**
	 * Creates a lock
	 *
	 * @param  string $key  the key to lock
	 * @return boolean      true if successful, else false
	 */
	protected function _lock($key) {
		return eaccelerator_lock($key);
	}

	/**
	 * Releases a lock
	 *
	 * @param  string $key  the key to unlock
	 * @return boolean      true if successful, else false
	 */
	protected function _unlock($key) {
		return eaccelerator_unlock($key);
	}

	protected function _increment($key) {
		$value = eaccelerator_get($key);
		return eaccelerator_put($key, $value + 1) !== false;
	}
}
