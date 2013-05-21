<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace wv\BabelCache\Cache;

use wv\BabelCache\AdapterInterface;
use wv\BabelCache\CacheInterface;
use wv\BabelCache\Exception;
use wv\BabelCache\IncrementInterface;
use wv\BabelCache\LockingInterface;
use wv\BabelCache\Util;

/**
 * Generic namespaced cache, based on key-value adapters
 *
 * This class encapsulates the algorithms used for creating a namespaced
 * environment in caching systems that don't really support it. The concrete
 * implementations will only wrap the specific functions added by the PECL
 * module.
 *
 * @package BabelCache.Cache
 */
class Generic implements CacheInterface {
	protected $adapter;   ///< AdapterInterface  the actual storage system
	protected $prefix;    ///< string            a prefix to run mutliple projects in an isolated way
	protected $versions;  ///< array             runtime cache of versions

	const MAX_KEY_LENGTH = 200;

	public function __construct(AdapterInterface $adapter, $prefix = '') {
		$this->adapter  = $adapter;
		$this->prefix   = $prefix;
		$this->versions = array();
	}

	/**
	 * Get the wrapped adapter
	 *
	 * @return AdapterInterface
	 */
	public function getAdapter() {
		return $this->adapter;
	}

	/**
	 * Sets the key prefix
	 *
	 * The key prefix will be put in front of the generated cache key, so that
	 * multiple installations of the same system can co-exist on the same
	 * machine.
	 *
	 * @param string $prefix  the prefix to use (will be trimmed)
	 */
	public function setPrefix($prefix) {
		$this->prefix = trim($prefix);
	}

	/**
	 * Sets a value
	 *
	 * This method will put a value into the cache. If it already exists, it
	 * will be overwritten.
	 *
	 * @throws Exception          if an error occured
	 * @param  string $namespace  the namespace to use
	 * @param  string $key        the object key
	 * @param  mixed  $value      the value to store
	 * @return mixed              the set value
	 */
	public function set($namespace, $key, $value) {
		$key  = $this->getFullKey($namespace, $key);  // namespace$key
		$path = $this->createVersionPath($key, true); // foo@X.bla@Y...
		$path = $this->getPrefixed($path);            // prefix/foo@X.bla@Y...

		if (!$this->adapter->set($path, $value)) {
			throw new Exception('Error setting value @ '.$key.'!');
		}

		return $value;
	}

	/**
	 * Checks whether a value exists
	 *
	 * @param  string $namespace  the namespace to use
	 * @param  string $key        the object key
	 * @return boolean            true if the value exists, else false
	 */
	public function exists($namespace, $key) {
		$key  = $this->getFullKey($namespace, $key);   // namespace$key
		$path = $this->createVersionPath($key, false); // foo@X.bla@Y...

		// if a valid path could be constructed, check the element's existence
		if ($path !== false) {
			$path = $this->getPrefixed($path); // prefix/foo@X.bla@Y...
			$path = $this->adapter->exists($path);
		}

		return $path !== false;
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
	 * @return mixed              the found value or $default
	 */
	public function get($namespace, $key, $default = null, &$found = null) {
		$key  = $this->getFullKey($namespace, $key);   // namespace$key
		$path = $this->createVersionPath($key, false); // foo@X.bla@Y...

		if ($path === false) {
			return $default;
		}

		$path  = $this->getPrefixed($path); // prefix/foo@X.bla@Y...
		$value = $this->adapter->get($path, $found);

		return $found ? $value : $default;
	}

	/**
	 * Removes a single value from the cache
	 *
	 * @param  string $namespace  the namespace to use
	 * @param  string $key        the object key
	 * @return boolean            true if the value was deleted, else false
	 */
	public function remove($namespace, $key) {
		$key  = $this->getFullKey($namespace, $key);   // namespace$key
		$path = $this->createVersionPath($key, false); // foo@X.bla@Y...
		$path = $this->getPrefixed($path);             // prefix/foo@X.bla@Y...

		return $this->adapter->remove($path);
	}

	/**
	 * Removes a single value from the cache
	 *
	 * @deprecated  since 2.0, use remove() instead
	 *
	 * @param  string $namespace  the namespace to use
	 * @param  string $key        the object key
	 * @return boolean            true if the value was deleted, else false
	 */
	public function delete($namespace, $key) {
		return $this->remove($namespace, $key);
	}

	/**
	 * Removes all values in a given namespace
	 *
	 * This method will flush all values by making them unavailable. For this,
	 * the version number of the flushed namespace is increased by one.
	 *
	 * Pay attention that all in-memory caches will ignore $recursive and always
	 * clear fully recursive. It's not possible to clear only one level and
	 * it's generally better to clear more values than too few.
	 *
	 * @param  string  $namespace  the namespace to use
	 * @param  boolean $recursive  This parameter will always be ignored and set to true.
	 * @return boolean             true if the flush was successful, else false
	 */
	public function clear($namespace, $recursive = false) {
		Util::checkString($namespace, 'namespace');

		$fullKey = $namespace.'$';
		$path    = $this->createVersionPath($fullKey, false, true);

		if ($path === false) {
			return false;
		}

		// prefix/version:foo@123.bar@45.mynamespace++;

		$this->versions = array();

		return $this->increment($this->getPrefixed('version:'.$path)) !== false;
	}

	/**
	 * Removes all values in a given namespace
	 *
	 * @deprecated  since 2.0, use clear() instead
	 *
	 * @param  string  $namespace  the namespace to use
	 * @param  boolean $recursive  This parameter will always be ignored and set to true.
	 * @return boolean             true if the flush was successful, else false
	 */
	public function flush($namespace, $recursive = false) {
		return $this->clear($namespace, $recursive);
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
		$key = $this->getFullKey($namespace, $key);

		return $this->lockKey($key);
	}

	/**
	 * Releases a lock
	 *
	 * This method will remove a lock for a specific key.
	 *
	 * @param  string $namespace  the namespace
	 * @param  string $key        the key
	 * @return boolean            true if the lock was released, else false
	 */
	public function unlock($namespace, $key) {
		$key = $this->getFullKey($namespace, $key);

		return $this->unlockKey($key);
	}

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
	public function waitForLockRelease($namespace, $key, $default = null, $maxWaitTime = 3, $checkInterval = 750) {
		$fullKey        = $this->getFullKey($namespace, $key);
		$start          = microtime(true);
		$waited         = 0;
		$checkInterval *= 1000;

		while ($waited < $maxWaitTime && $this->hasLock($fullKey)) {
			usleep($checkInterval);
			$waited = microtime(true) - $start;
		}

		if (!$this->hasLock($fullKey)) {
			return $this->get($namespace, $key, $default);
		}

		return $default;
	}

	/**
	 * Creates the full key
	 *
	 * This method will concat the namespace and key and check the length so that
	 * the resulting key will not be truncated by the caching system.
	 *
	 * @throws BabelCache_Exception  if the resulting key is too long
	 * @param  string $namespace     the namespace
	 * @param  string $key           the key
	 * @return string                'namespace$key'
	 */
	protected function getFullKey($namespace, $key) {
		$fullKey = Util::getFullKeyHelper($namespace, $key);
		$testKey = $this->getPrefixed($fullKey);

		if (strlen($testKey) > self::MAX_KEY_LENGTH) {
			throw new Exception('The given key is too long. At most '.self::MAX_KEY_LENGTH.' characters are allowed.');
		}

		return $fullKey;
	}

	/**
	 * Prepends a key with the prefix
	 *
	 * This method will return the key unaltered if no prefix is given, else it
	 * will put the prefix plus a slash in front of the key.
	 *
	 * @param  string $fullKey  the full key (namespace + key)
	 * @return string           'prefix/namespace$key' or 'namespace$key'
	 */
	protected function getPrefixed($fullKey) {
		return strlen($this->prefix) === 0 ? $fullKey : $this->prefix.'/'.$fullKey;
	}

	/**
	 * Creates the final, versioned key to use
	 *
	 * This method is the heart of the whole caching system. Its job is it to
	 * put version numbers to each namespace. Every version is itself being
	 * stored as a regular cache value.
	 *
	 * So when you put in
	 *
	 * @verbatim
	 * my.super.cool.namespace$mykey
	 * @endverbatim
	 *
	 * this method will return
	 *
	 * @verbatim
	 * my@1.super@1.cool@1.namespace@1$key
	 * @endverbatim
	 *
	 * See the docs for more information on this process.
	 *
	 * @param  string  $fullKey             the full key ('namespace$key' without the prefix)
	 * @param  boolean $createIfMissing     if not version is found for a partial namespace, should a new one be created?
	 * @param  boolean $excludeLastVersion  if true, the last part of the namespace won't be versioned
	 * @return string                       the full key to be used when calling the caching system
	 */
	protected function createVersionPath($fullKey, $createIfMissing = true, $excludeLastVersion = false) {
		list($pathString, $keyName) = explode('$', $fullKey);

		$path  = array();
		$steps = explode('.', $pathString);

		foreach ($steps as $idx => $step) {
			$path[]         = $step;
			$currentPath    = implode('.', $path);
			$currentVersion = $this->getVersion($currentPath);

			if ($currentVersion === null) {
				if ($createIfMissing) {
					$currentVersion = 1;
				}
				else {
					return false;
				}

				$this->setVersion($currentPath, $currentVersion);
			}

			$path[$idx] = $step.'@'.$currentVersion;
		}

		if ($excludeLastVersion) {
			$lastNode = array_pop($path);
			$lastNode = explode('@', $lastNode, 2);
			$lastNode = reset($lastNode);

			$path[] = $lastNode;
		}

		$path = implode('.', $path);

		if (!empty($keyName)) {
			$path .= '$'.$keyName;
		}

		return $path;
	}

	/**
	 * Reads a version number
	 *
	 * This method reads a version number from the cache and stores it in
	 * a runtime cache (= a simple array). This reduces the number of calls to
	 * the caching system and speeds up the creation of versioned keys.
	 *
	 * @param  string $path  the path for which the version number should be fetched
	 * @return int           the version or null if non was found
	 */
	protected function getVersion($path) {
		if (!isset($this->versions[$path])) {
			$version = $this->adapter->get($this->getPrefixed('version:'.$path));
			$this->versions[$path] = empty($version) ? null : $version;
		}

		return $this->versions[$path];
	}

	/**
	 * Stores a version number
	 *
	 * This method just writes the version to the cache and puts a copy in the
	 * runtime cache.
	 *
	 * @param string $path     the path for which the version applies
	 * @param int    $version  the version number
	 */
	protected function setVersion($path, $version) {
		$this->adapter->set($this->getPrefixed('version:'.$path), $version);
		$this->versions[$path] = $version;
	}

	/**
	 * Checks if a key is locked
	 *
	 * @param  string $key  the key to check
	 * @return boolean      true if a lock exists, else false
	 */
	protected function hasLock($key) {
		$fullKey = $this->getPrefixed('lock:'.$key);
		$hasLock = $this->lockKey($key) === false;

		// If we just created an accidental lock, remove it.
		if (!$hasLock) {
			$this->unlockKey($key);
		}

		return $hasLock;
	}

	/**
	 * Lock a key
	 *
	 * The key is supposed to be the full key, i.e. a combination of namespace
	 * and element key.
	 *
	 * @param  string $key  the key to lock
	 * @return boolean      true if the lock was aquired, else false
	 */
	protected function lockKey($key) {
		if ($this->adapter instanceof LockingInterface) {
			return $this->adapter->lock($key);
		}
		else {
			$fullKey = $this->getPrefixed('lock:'.$key);
			$isset   = $this->adapter->exists($fullKey);

			// lock exists already
			if ($isset === true) {
				return false;
			}

			return $this->adapter->set($fullKey, 1);
		}
	}

	/**
	 * Unlock a key
	 *
	 * The key is supposed to be the full key, i.e. a combination of namespace
	 * and element key.
	 *
	 * @param  string $key  the key to unlock
	 * @return boolean      true if the lock was released or there was no lock, else false
	 */
	protected function unlockKey($key) {
		if ($this->adapter instanceof LockingInterface) {
			return $this->adapter->unlock($key);
		}
		else {
			$fullKey = $this->getPrefixed('lock:'.$key);
			$isset   = $this->adapter->exists($fullKey);

			// no lock, everything's shiny
			if ($isset === false) {
				return true;
			}

			return $this->adapter->remove($fullKey);
		}
	}

	/**
	 * Increment a key in a portable way
	 *
	 * @param  string $key  the element's key
	 * @return int          the value after it has been incremented or false if the operation failed
	 */
	protected function increment($key) {
		if ($this->adapter instanceof IncrementInterface) {
			return $this->adapter->increment($key);
		}

		$found = false;
		$value = $this->adapter->get($key, $found);
		$value = $found ? ($value + 1) : 1;

		return $this->adapter->set($key, $value) ? $value : false;
	}
}