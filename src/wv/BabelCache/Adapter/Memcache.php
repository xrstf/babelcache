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
use wv\BabelCache\Exception;
use wv\BabelCache\IncrementInterface;
use wv\BabelCache\LockingInterface;

/**
 * Memcache wrapper
 *
 * This class wraps the memcache extension of PHP. Don't mix it up with the
 * memcached (with d!) extension, for which you have to use BabelCache_Memcached.
 *
 * @see     http://www.php.net/manual/de/book.memcache.php
 * @package BabelCache.Adapter
 */
class Memcache implements AdapterInterface, IncrementInterface {
	protected $memcached = null;  ///< Memcache  the current Memcache instance

	public function __construct() {
		$this->memcached = new \Memcache();
	}

	/**
	 * Checks whether a caching system is avilable
	 *
	 * This method will be called before an instance is created. It is supposed
	 * to check for the required functions and whether user data caching is
	 * enabled.
	 *
	 * @return boolean  true if the cache can be used, else false
	 */
	public static function isAvailable() {
		return class_exists('Memcache');
	}

	/**
	 * Get wrapped Memcache instance
	 *
	 * @return Memcache
	 */
	public function getMemcached() {
		return $this->memcached;
	}

	/**
	 *
	 * @param string $host           the port
	 * @param int    $port           the port
	 * @param int    $weight         weight as integer / decides ammount of keys saved on this server
	 * @throws BabelCache_Exception  if the connection could not be established
	 */
	public function addServer($host, $port = 11211, $weight = 0) {
		if (!$this->memcached->addServer($host, $port, true, $weight)) {
			throw new Exception('Could not connect to Memcache @ '.$host.':'.$port.'!');
		}
	}

	public function addServerEx($host, $port = 11211, $weight = 0, $persistent = true, $timeout = 1, $retryInterval = 15, $status = true, $failureCallback = null) {
		return $this->memcached->addServer($host, $port, $persistent, $weight, $timeout, $retryInterval, $status, $failureCallback);
	}

	public function getMemcachedVersion() {
		return $this->memcached->getVersion();
	}

	public function getStats() {
		return $this->memcached->getStats();
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
		$value = $this->memcached->get($key);
		$found = $value !== false;

		return $found ? unserialize($value) : $value;
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
		return $this->memcached->set($key, serialize($value), 0, $ttl);
	}

	/**
	 * Removes a single value from the cache
	 *
	 * @param  string $key  the object key
	 * @return boolean      true if the value was deleted, else false
	 */
	public function remove($key) {
		return $this->memcached->delete($key);
	}

	/**
	 * Checks whether a value exists
	 *
	 * @param  string $key  the object key
	 * @return boolean      true if the value exists, else false
	 */
	public function exists($key) {
		return $this->memcached->get($key) !== false;
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
		return $this->memcached->increment($key);
	}
}
