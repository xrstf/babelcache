<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class BabelCache_APC extends BabelCache_Abstract {
	private $hasExistsMethod = null;

	public function __construct() {
		$this->hasExistsMethod = function_exists('apc_exists');
	}

	public static function isAvailable() {
		// Wir müssen auch prüfen, ob Werte gespeichert werden können (oder ob nur der Opcode-Cache aktiviert ist).
		return function_exists('apc_store') && apc_store('test', 1, 1);
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
		return apc_store($key, $value, $expiration);
	}

	protected function _set($key, $value, $expiration) {
		if (!$this->hasExistsMethod) $value = serialize($value);
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

	protected function _lock($key) {
		return apc_add($key, 1);
	}

	protected function _unlock($key) {
		return apc_delete($key);
	}
}
