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

/**
 * Zend Server
 *
 * This class wraps the methods provided by Zend Server for caching vardata.
 * Please note that this implementation does not use the native namespacing
 * features, but the generic implementation of BabelCache.
 *
 * @see     http://files.zend.com/help/Zend-Platform/zend_cache_api.htm
 * @package BabelCache.Adapter
 */
class ZendServer implements AdapterInterface {
	public static function isAvailable() {
		// Wir müssen auch prüfen, ob Werte gespeichert werden können (oder ob nur der Opcode-Cache aktiviert ist).
		return function_exists('zend_shm_cache_store') && ini_get('zend_datacache.enable') && zend_shm_cache_store('test', 1, 1);
	}

	public function get($key, &$found = null) {
		$value = zend_shm_cache_fetch($key);
		$found = $value !== null;

		return $value;
	}

	public function set($key, $value, $expiration = null) {
		return zend_shm_cache_store($key, $value, $expiration);
	}

	public function remove($key) {
		return zend_shm_cache_delete($key);
	}

	public function exists($key) {
		return zend_shm_cache_fetch($key) !== null;
	}

	public function clear() {
		return zend_shm_cache_clear();
	}
}
