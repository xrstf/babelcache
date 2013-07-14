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
use wv\BabelCache\Factory;
use wv\BabelCache\IncrementInterface;
use wv\BabelCache\LockingInterface;

/**
 * Memcache wrapper
 *
 * This class wraps the memcache extension of PHP. Don't mix it up with the
 * memcached (with d!) extension, for which you have to use Adapter\Memcached.
 *
 * @see     http://www.php.net/manual/de/book.memcache.php
 * @package BabelCache.Adapter
 */
class Memcache implements AdapterInterface, IncrementInterface, LockingInterface {
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
	 * @param  Factory $factory  the project's factory to give the adapter some more knowledge
	 * @return boolean           true if the cache can be used, else false
	 */
	public static function isAvailable(Factory $factory = null) {
		if (!class_exists('Memcache')) return false;
		if (!$factory) return true;

		$servers = $factory->getMemcachedAddresses();

		return !empty($servers);
	}

	/**
	 * Get wrapped Memcache instance
	 *
	 * @return Memcache
	 */
	public function getMemcache() {
		return $this->memcached;
	}

	/**
	 * Get wrapped Memcache instance
	 *
	 * This method is an alias for getMemcache().
	 *
	 * @return Memcache
	 */
	public function getMemcached() {
		return $this->memcached;
	}

	/**
	 * Add a server to the rotation
	 *
	 * Note that this does not actually connect to the server yet, so you cannot
	 * use this to check for valid hosts.
	 *
	 * @param string $host    the host name
	 * @param int    $port    the port
	 * @param int    $weight  weight as integer / decides ammount of keys saved on this server
	 */
	public function addServer($host, $port = 11211, $weight = 1) {
		$this->memcached->addServer($host, $port, true, $weight);
	}

	public function addServerEx($host, $port = 11211, $weight = 1, $persistent = true, $timeout = 1, $retryInterval = 15, $status = true, $failureCallback = null) {
		$this->memcached->addServer($host, $port, $persistent, $weight, $timeout, $retryInterval, $status, $failureCallback);
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
	 * This method will try to read the value from the cache.
	 *
	 * @param  string  $key    the object key
	 * @param  boolean $found  will be set to true or false when the method is finished
	 * @return mixed           the found value or null
	 */
	public function get($key, &$found = null) {
		// always request an array with one key, so we can properly distinguish
		// between not-found and false values
		$values = $this->memcached->get(array($key));
		$found  = count($values) === 1;

		if (!$found) {
			return null;
		}

		$value = reset($values);

		return (is_int($value) || ctype_digit($value)) ? (int) $value : unserialize($value);
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
		// store integers as plain values, so we can easily increment them.
		$value = is_int($value) ? $value : serialize($value);

		return $this->memcached->set($key, $value, 0, $ttl);
	}

	/**
	 * Deletes a single value from the cache
	 *
	 * @param  string $key  the object key
	 * @return boolean      true if the value was deleted, else false
	 */
	public function delete($key) {
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
	 * Deletes all values
	 *
	 * @return boolean  true if the flush was successful, else false
	 */
	public function clear() {
		return $this->memcached->flush();
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

	/**
	 * Locks a key
	 *
	 * This method will create a lock for a specific key.
	 *
	 * @param  string $key  the key
	 * @return boolean      true if the lock was aquired, else false
	 */
	public function lock($key) {
		return $this->memcached->add('lock:'.$key, 1);
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
		return $this->memcached->delete('lock:'.$key);
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
