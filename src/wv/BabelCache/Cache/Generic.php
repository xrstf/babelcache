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
use wv\BabelCache\Factory;
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
	const LOCK_NAMESPACE = '__locks__';

	public function __construct(AdapterInterface $adapter, $prefix = '') {
		$this->adapter  = $adapter;
		$this->prefix   = $prefix;
		$this->versions = array();
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
	 * Deletes a single value from the cache
	 *
	 * @param  string $namespace  the namespace to use
	 * @param  string $key        the object key
	 * @return boolean            true if the value was deleted, else false
	 */
	public function delete($namespace, $key) {
		$key  = $this->getFullKey($namespace, $key);   // namespace$key
		$path = $this->createVersionPath($key, false); // foo@X.bla@Y...
		$path = $this->getPrefixed($path);             // prefix/foo@X.bla@Y...

		return $this->adapter->delete($path);
	}

	/**
	 * Deletes all values in a given namespace
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

		// clear the locks
		// It's okay if this fails, maybe there was no lock yet and hence no version key.

		$this->increment($this->getPrefixed('version:'.self::LOCK_NAMESPACE));
		$this->versions = array();

		// build the path up until the namespace to clear

		$fullKey = $namespace.'$';
		$path    = $this->createVersionPath($fullKey, false, true);

		if ($path === false) {
			return false;
		}

		// prefix/version:foo@123.bar@45.mynamespace++;

		if ($this->increment($this->getPrefixed('version:'.$path)) !== false) {
			$this->versions = array();
			return true;
		}

		// @codeCoverageIgnoreStart
		return false;
		// @codeCoverageIgnoreEnd
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
		$key = $this->getFullLockKey($namespace, $key);

		if ($this->adapter instanceof LockingInterface) {
			return $this->adapter->lock($key);
		}
		else {
			$isset = $this->adapter->exists($key);

			// lock exists already
			if ($isset === true) {
				return false;
			}

			return $this->adapter->set($key, 1);
		}
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
		$key = $this->getFullLockKey($namespace, $key);

		if ($this->adapter instanceof LockingInterface) {
			return $this->adapter->unlock($key);
		}
		else {
			$isset = $this->adapter->exists($key);

			// no lock, everything's shiny
			if ($isset === false) {
				return false;
			}

			return $this->adapter->delete($key);
		}
	}

	/**
	 * Check if a key is locked
	 *
	 * @param  string $namespace  the namespace
	 * @param  string $key        the key
	 * @return boolean            true if the key is locked, else false
	 */
	public function hasLock($namespace, $key) {
		$key = $this->getFullLockKey($namespace, $key);

		if ($this->adapter instanceof LockingInterface) {
			return $this->adapter->hasLock($key);
		}
		else {
			return $this->adapter->exists($key);
		}
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
	 * Increment a key in a portable way
	 *
	 * @param  string $key  the element's key
	 * @return mixed        the value after it has been incremented or false if the operation failed
	 */
	protected function increment($key) {
		if ($this->adapter instanceof IncrementInterface) {
			$result = $this->adapter->increment($key);
		}
		else {
			$found = false;
			$value = $this->adapter->get($key, $found);

			if (!$found) {
				return false;
			}

			$value++;

			$result = $this->adapter->set($key, $value) ? $value : false;
		}

		return $result;
	}

	protected function getFullLockKey($namespace, $key) {
		return $this->getPrefixed($this->createVersionPath(self::LOCK_NAMESPACE.'$'.sha1($namespace.'$'.$key), true));
	}
}
