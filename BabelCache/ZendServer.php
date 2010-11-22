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
 * Zend Server
 *
 * This class wraps the methods provied by Zend Server for caching vardata.
 * Please note that this implementation does not use the native namespacing
 * features, but the generic implementation of BabelCache_Abstract.
 *
 * @author Christoph Mewes
 * @see    http://files.zend.com/help/Zend-Platform/zend_cache_api.htm
 */
class BabelCache_ZendServer extends BabelCache_Abstract {
	public function getMaxKeyLength() {
		return 200; // unbekannt -> Schätzwert
	}

	public function hasLocking() {
		return false;
	}

	public static function isAvailable() {
		// Wir müssen auch prüfen, ob Werte gespeichert werden können (oder ob nur der Opcode-Cache aktiviert ist).
		return function_exists('zend_shm_cache_store') && ini_get('zend_datacache.enable') && zend_shm_cache_store('test', 1, 1);
	}

	protected function _getRaw($key) {
		return zend_shm_cache_fetch($key);
	}

	protected function _get($key) {
		return zend_shm_cache_fetch($key);
	}

	protected function _setRaw($key, $value, $expiration) {
		return zend_shm_cache_store($key, $value, $expiration);
	}

	protected function _set($key, $value, $expiration) {
		return zend_shm_cache_store($key, $value, $expiration);
	}

	protected function _delete($key) {
		return zend_shm_cache_delete($key);
	}

	protected function _isset($key) {
		return zend_shm_cache_fetch($key) !== false;
	}

	protected function _increment($key) {
		$value = zend_shm_cache_fetch($key);
		return zend_shm_cache_store($key, $value + 1) !== false;
	}
}
