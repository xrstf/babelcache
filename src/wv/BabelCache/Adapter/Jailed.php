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
use wv\BabelCache\CacheInterface;
use wv\BabelCache\Factory;
use wv\BabelCache\LockingInterface;

/**
 * Jailed cache adapter
 *
 * This adapter actually wraps a full-blown cache, using its namespacing to
 * provide a jailed cache adapter (so you can use APC as your system of choice,
 * use different jails and allow for them to be cleared independently).
 *
 * @package BabelCache.Adapter
 */
class Jailed implements AdapterInterface, LockingInterface {
	protected $cache;
	protected $namespace;
	protected $recursive;

	/**
	 * Constructor
	 *
	 * @param CacheInterface $cache      the cache to be used
	 * @param string         $namespace  the root namespace
	 * @param boolean        $recursive  if true, clear() will clear recursively, otherwise it will only clear the namespace itself
	 */
	public function __construct(CacheInterface $cache, $namespace, $recursive) {
		$this->cache     = $cache;
		$this->namespace = $namespace;
		$this->recursive = !!$recursive;
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
		return true;
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
		return $this->cache->get($this->namespace, $key, null, $found);
	}

	/**
	 * Sets a value
	 *
	 * This method will put a value into the cache. If it already exists, it
	 * will be overwritten.
	 *
	 * @param  string $key    the object key
	 * @param  mixed  $value  the value to store
	 * @param  mixed  $ttl    timeout in seconds
	 * @return boolean        true on success, else false
	 */
	public function set($key, $value, $ttl = null) {
		return $this->cache->set($this->namespace, $key, $value, $ttl);
	}

	/**
	 * Deletes a single value from the cache
	 *
	 * @param  string $key  the object key
	 * @return boolean      true if the value was deleted, else false
	 */
	public function delete($key) {
		return $this->cache->delete($this->namespace, $key);
	}

	/**
	 * Checks whether a value exists
	 *
	 * @param  string $key  the object key
	 * @return boolean      true if the value exists, else false
	 */
	public function exists($key) {
		return $this->cache->exists($this->namespace, $key);
	}

	/**
	 * Deletes all values
	 *
	 * @return boolean  true if the flush was successful, else false
	 */
	public function clear() {
		return $this->cache->clear($this->namespace, $this->recursive);
	}

	/**
	 * Creates a lock
	 *
	 * @param  string $key  the key to lock
	 * @return boolean      true if successful, else false
	 */
	public function lock($key) {
		return $this->cache->lock($this->namespace, $key);
	}

	/**
	 * Releases a lock
	 *
	 * @param  string $key  the key to unlock
	 * @return boolean      true if successful, else false
	 */
	public function unlock($key) {
		return $this->cache->unlock($this->namespace, $key);
	}

	/**
	 * Check if a key is locked
	 *
	 * @param  string $key  the key
	 * @return boolean      true if the key is locked, else false
	 */
	public function hasLock($key) {
		return $this->cache->hasLock($this->namespace, $key);
	}
}
