<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * Generic cache interface
 *
 * This interface is used for all provided caches and provides a common set of
 * methods that all caches support. It's kind of the least common denominator.
 *
 * @author Christoph Mewes
 */
interface BabelCache_Interface {
	/**
	 * Checks whether a caching system is avilable
	 *
	 * This method will be called before an instance is created. It is supposed
	 * to check for the required functions and whether user data caching is
	 * enabled.
	 *
	 * @return boolean  true if the cache can be used, else false
	 */
	public static function isAvailable();

	/**
	 * Sets a value
	 *
	 * This method will put a value into the cache. If it already exists, it
	 * will be overwritten.
	 *
	 * @param  string $namespace     the namespace to use
	 * @param  string $key           the object key
	 * @param  mixed  $value         the value to store
	 * @return mixed                 the set value
	 */
	public function set($namespace, $key, $value);

	/**
	 * Gets a value out of the cache
	 *
	 * This method will try to read the value from the cache. If it's not found,
	 * $default will be returned.
	 *
	 * @param  string $namespace  the namespace to use
	 * @param  string $key        the object key
	 * @param  mixed  $default    the default value
	 * @return mixed              the found value or $default
	 */
	public function get($namespace, $key, $default = null);

	/**
	 * Removes a single value from the cache
	 *
	 * @param  string $namespace  the namespace to use
	 * @param  string $key        the object key
	 * @return boolean            true if the value was deleted, else false
	 */
	public function delete($namespace, $key);

	/**
	 * Checks whether a value exists
	 *
	 * @param  string $namespace  the namespace to use
	 * @param  string $key        the object key
	 * @return boolean            true if the value exists, else false
	 */
	public function exists($namespace, $key);

	/**
	 * Locks a key
	 *
	 * This method will create a lock for a specific key. Caches that don't
	 * support native locking will use a special "lock:key" value.
	 *
	 * @param  string $namespace  the namespace
	 * @param  string $key        the key
	 * @param  int    $duration   How long should the lock be alive?
	 * @return boolean            true if the lock was aquired, else false
	 */
	public function lock($namespace, $key, $duration = 1);

	/**
	 * Releases a lock
	 *
	 * This method will remove a lock for a specific key.
	 *
	 * @param  string $namespace  the namespace
	 * @param  string $key        the key
	 * @return boolean            true if the lock was released, else false
	 */
	public function unlock($namespace, $key);

	/**
	 * Waits for a lock to be released
	 *
	 * This method will wait for a specific amount of time for the lock to be
	 * released. For this, it constantly checks the lock (tweak the check
	 * interval with the last parameter).
	 *
	 * When the maximum waiting time elapsed, the $default value will be
	 * returned. Else the value will be read from the cache.
	 *
	 * @param  string $namespace      the namespace
	 * @param  string $key            the key
	 * @param  mixed  $default        the value to return if the lock does not get released
	 * @param  int    $maxWaitTime    the maximum waiting time (in seconds)
	 * @param  int    $checkInterval  the check interval (in milliseconds)
	 * @return mixed                  the value from the cache if the lock was released, else $default
	 */
	public function waitForObject($namespace, $key, $default = null, $maxWaitTime = 3, $checkInterval = 50);

	/**
	 * Removes all values in a given namespace
	 *
	 * This method will flush all values by making them unavailable. For this,
	 * the version number of the flushed namespace is increased by one.
	 *
	 * @param  string  $namespace  the namespace to use
	 * @param  boolean $recursive  if set to true, all child namespaces will be cleared as well
	 * @return boolean             true if the flush was successful, else false
	 */
	public function flush($namespace, $recursive = false);
}
