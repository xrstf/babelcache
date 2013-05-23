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
		// Wir müssen auch prüfen, ob Werte gespeichert werden können (oder ob nur der Opcode-Cache aktiviert ist).
		return function_exists('zend_shm_cache_store') && ini_get('zend_datacache.enable') && zend_shm_cache_store('test', 1, 1);
	}

	/**
	 * Gets a value out of the cache
	 *
	 * This method will try to read the value from the cache. If it's not found,
	 * $default will be returned.
	 *
	 * @param  string  $key    the object key
	 * @param  boolean $found  will be set to true or false when the method is finished
	 * @return mixed           the found value or null
	 */
	public function get($key, &$found = null) {
		$value = zend_shm_cache_fetch($key);
		$found = $value !== null;

		return $value;
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
		return zend_shm_cache_store($key, $value, $expiration);
	}

	/**
	 * Removes a single value from the cache
	 *
	 * @param  string $key  the object key
	 * @return boolean      true if the value was deleted, else false
	 */
	public function remove($key) {
		return zend_shm_cache_delete($key);
	}

	/**
	 * Checks whether a value exists
	 *
	 * @param  string $key  the object key
	 * @return boolean      true if the value exists, else false
	 */
	public function exists($key) {
		return zend_shm_cache_fetch($key) !== null;
	}

	/**
	 * Removes all values
	 *
	 * @return boolean  true if the flush was successful, else false
	 */
	public function clear() {
		return zend_shm_cache_clear();
	}
}
