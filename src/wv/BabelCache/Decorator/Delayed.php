<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace wv\BabelCache\Decorator;

use wv\BabelCache\CacheInterface;
use wv\BabelCache\Factory;

class Delayed extends Base implements CacheInterface {
	protected $delay;

	/**
	 * Constructor
	 *
	 * @param CacheInterface $realCache  the caching instance to be wrapped
	 * @param int            $delay      number of microseconds to delay every access
	 */
	public function __construct(CacheInterface $realCache, $delay) {
		parent::__construct($realCache);

		$this->delay = (int) $delay;
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
	 * Sets a value
	 *
	 * This method will put a value into the cache. If it already exists, it
	 * will be overwritten.
	 *
	 * @param  string $namespace  namespace to use
	 * @param  string $key        object key
	 * @param  mixed  $value      value to store
	 * @param  mixed  $ttl        timeout in seconds
	 * @return mixed              the set value
	 */
	public function set($namespace, $key, $value, $ttl = null) {
		return $this->wait()->cache->set($namespace, $key, $value, $ttl);
	}

	/**
	 * Gets a value out of the cache
	 *
	 * This method will try to read the value from the cache. If it's not found,
	 * $default will be returned.
	 *
	 * @param  string $namespace  the namespace to use
	 * @param  string $key        the object key
	 * @param  mixed  $default    the default value
	 * @return mixed              the found value if not expired or $default
	 */
	public function get($namespace, $key, $default = null, &$found = null) {
		return $this->wait()->cache->get($namespace, $key, $default, $found);
	}

	/**
	 * Deletes a single value from the cache
	 *
	 * @param  string $namespace  the namespace to use
	 * @param  string $key        the object key
	 * @return boolean            true if the value was deleted, else false
	 */
	public function delete($namespace, $key) {
		return $this->wait()->cache->delete($namespace, $key);
	}

	/**
	 * Checks whether a value exists
	 *
	 * @param  string $namespace  the namespace to use
	 * @param  string $key        the object key
	 * @return boolean            true if the value exists, else false
	 */
	public function exists($namespace, $key) {
		return $this->wait()->cache->exists($namespace, $key);
	}

	/**
	 * Deletes all values in a given namespace
	 *
	 * This method will delete all values by making them unavailable. For this,
	 * the version number of the flushed namespace is increased by one.
	 *
	 * Implementations are *not* required to support non-recursive flushes. If
	 * those are not supported, a recursive flush must be performed instead.
	 * Userland code should assume that every clear operation is recursive and
	 * the $recursive flag is a mere optimization hint.
	 *
	 * @param  string  $namespace  the namespace to use
	 * @param  boolean $recursive  if set to true, all child namespaces will be cleared as well
	 * @return boolean             true if the flush was successful, else false
	 */
	public function clear($namespace, $recursive = false) {
		return $this->wait()->cache->clear($namespace, $recursive);
	}

	/**
	 * Locks a key
	 *
	 * This method will create a lock for a specific key. Caches that don't
	 * support native locking will use a special "lock:key" value.
	 *
	 * @param  string $namespace  the namespace
	 * @param  string $key        the key
	 * @return boolean            true if the lock was aquired, else false
	 */
	public function lock($namespace, $key) {
		return $this->wait()->cache->lock($namespace, $key);
	}

	/**
	 * Releases a lock
	 *
	 * This method will delete a lock for a specific key.
	 *
	 * @param  string $namespace  the namespace
	 * @param  string $key        the key
	 * @return boolean            true if the lock was released, else false
	 */
	public function unlock($namespace, $key) {
		return $this->wait()->cache->unlock($namespace, $key);
	}

	/**
	 * Check if a key is locked
	 *
	 * @param  string $namespace  the namespace
	 * @param  string $key        the key
	 * @return boolean            true if the key is locked, else false
	 */
	public function hasLock($namespace, $key) {
		return $this->wait()->cache->hasLock($namespace, $key);
	}

	/**
	 * Waits for the configured time
	 *
	 * @return Delayed  reference to self
	 */
	protected function wait() {
		usleep($this->delay);

		return $this;
	}
}
