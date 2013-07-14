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
use Predis\Client;

/**
 * Redis adapter
 *
 * This adapter uses predis to connect with a Redis daemon.
 *
 * @see     http://redis.io/
 * @see     https://github.com/nrk/predis
 * @package BabelCache.Adapter
 */
class Redis implements AdapterInterface, IncrementInterface, LockingInterface {
	protected $client = null;  ///< Client  the current Predis Client

	public function __construct(Client $client) {
		$this->client = $client;
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
		if (!class_exists('Predis\Client')) return false;
		if (!$factory) return true;

		$servers = $factory->getRedisAddresses();

		return !empty($servers);
	}

	/**
	 * Get wrapped Client instance
	 *
	 * @return Client
	 */
	public function getClient() {
		return $this->client;
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
		$value = $this->client->get($key);
		$found = $value !== null;

		if (!$found) {
			return null;
		}

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
	public function set($key, $value) {
		// store integers as plain values, so we can easily increment them.
		$value = is_int($value) ? $value : serialize($value);

		return $this->client->set($key, $value);
	}

	/**
	 * Deletes a single value from the cache
	 *
	 * @param  string $key  the object key
	 * @return boolean      true if the value was deleted, else false
	 */
	public function delete($key) {
		return !!$this->client->del($key);
	}

	/**
	 * Checks whether a value exists
	 *
	 * @param  string $key  the object key
	 * @return boolean      true if the value exists, else false
	 */
	public function exists($key) {
		return $this->client->exists($key);
	}

	/**
	 * Deletes all values
	 *
	 * @return boolean  true if the flush was successful, else false
	 */
	public function clear() {
		return $this->client->flushdb();
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
		return $this->client->exists($key) ? $this->client->incr($key) : false;
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
		return $this->client->setnx('lock:'.$key, 1);
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
		return $this->client->del('lock:'.$key) === 1;
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
