<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * Base class for all in-memory caches
 *
 * This class encapsulates the algorithms used for creating a namespaced
 * environment in caching systems that don't really support it. The concrete
 * implementations will only wrap the specific functions added by the PECL
 * module.
 *
 * @author Christoph Mewes
 */
abstract class BabelCache_Abstract extends BabelCache implements BabelCache_Interface {
	protected $versions   = array(); ///< array   runtime cache of versions
	protected $prefix     = '';      ///< string  a prefix to run mutliple projects in an isolated way
	protected $expiration = 0;       ///< int     expiration time (0 = never expire)

	/**
	 * Returns the maximum length of a key
	 *
	 * This method should return the implementation specific maximum length for
	 * a key. 'Key' does not mean the BabelCache key, but the complete key that
	 * will be constructed based on the namespace, key and the current versions.
	 *
	 * @return int  the max length (guess if you don't know)
	 */
	abstract public function getMaxKeyLength();

	/**
	 * Does the caching system support locking natively?
	 *
	 * @return boolean  true if locking support is avilable, else false
	 */
	abstract public function hasLocking();

	/**
	 * Checks whether a caching system is avilable
	 *
	 * This method will be called before an instance is created. It is supposed
	 * to check for the required functions and whether user data caching is
	 * enabled.
	 *
	 * @return boolean
	 */
	public static function isAvailable() {
		return true;
	}

	/**
	 * Sets a value
	 *
	 * This method will put a value into the cache. If it already exists, it
	 * will be overwritten.
	 *
	 * @throws BabelCache_Exception  if an error occured
	 * @param  string $namespace     the namespace to use
	 * @param  string $key           the object key
	 * @param  mixed  $value         the value to store
	 * @return mixed                 the set value
	 */
	public function set($namespace, $key, $value) {
		$key  = $this->getFullKey($namespace, $key);  // namespace$key
		$path = $this->createVersionPath($key, true); // foo@X.bla@Y...
		$path = $this->getPrefixed($path);            // prefix/foo@X.bla@Y...

		if (!$this->_set($path, $value, $this->expiration)) {
			throw new BabelCache_Exception('Error setting value @ '.$key.'!');
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

		if ($path !== false) {
			$path = $this->getPrefixed($path); // prefix/foo@X.bla@Y...
			$path = $this->_isset($path);
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
	public function get($namespace, $key, $default = null) {
		$key  = $this->getFullKey($namespace, $key);   // namespace$key
		$path = $this->createVersionPath($key, false); // foo@X.bla@Y...

		if ($path === false) {
			return $default;
		}

		$path = $this->getPrefixed($path); // prefix/foo@X.bla@Y...
		return $this->_isset($path) ? $this->_get($path) : $default;
	}

	/**
	 * Removes a single value from the cache
	 *
	 * @param  string $namespace  the namespace to use
	 * @param  string $key        the object key
	 * @return boolean            true if the value was deleted, else false
	 */
	public function delete($namespace, $key) {
		$key  = $this->getFullKey($namespace, $key);   // namespace$key
		$path = $this->createVersionPath($key, false); // foo@X.bla@Y...
		$path = $this->getPrefixed($path);             // prefix/foo@X.bla@Y...

		return $this->_delete($path);
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
	public function flush($namespace, $recursive = false) {
		$this->checkString($namespace, 'namespace');

		$fullKey = $namespace.'$';
		$path    = $this->createVersionPath($fullKey, false, true);

		if ($path === false) {
			return false;
		}

		// prefix/version:foo@123.bar@45.mynamespace++;

		$this->versions = array();
		return $this->_increment($this->getPrefixed('version:'.$path), 1, $this->expiration) !== false;
	}

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
	public function lock($namespace, $key, $duration = 1) {
		$key = $this->getFullKey($namespace, $key);

		if ($this->hasLocking()) {
			return $this->_lock($key);
		}
		else {
			$fullKey = $this->getPrefixed('lock:'.$key);
			$isset   = $this->_isset($fullKey);

			if ($isset === true) {
				return false;
			}

			return $this->_setRaw($fullKey, 1, $duration);
		}
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

		if ($this->hasLocking()) {
			return $this->_unlock($key);
		}
		else {
			$fullKey = $this->getPrefixed('lock:'.$key);
			$isset   = $this->_isset($fullKey);

			if ($isset === false) {
				return true;
			}

			return $this->_delete($fullKey);
		}
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
	public function waitForObject($namespace, $key, $default = null, $maxWaitTime = 3, $checkInterval = 50) {
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
	 * Sets the expiration time
	 *
	 * @param int $exp  time in seconds until a value should be expired
	 */
	public function setExpiration($exp) {
		$this->expiration = (int) $exp;
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
	private function getFullKey($namespace, $key) {
		$fullKey   = $this->getFullKeyHelper($namespace, $key);
		$maxLength = $this->getMaxKeyLength();
		$testKey   = $this->getPrefixed($fullKey);

		if (strlen($testKey) > $maxLength) {
			throw new BabelCache_Exception('The given key is too long. At most '.$maxLength.' characters are allowed.');
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
	private function getPrefixed($fullKey) {
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
	private function createVersionPath($fullKey, $createIfMissing = true, $excludeLastVersion = false) {
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
	private function getVersion($path) {
		if (!isset($this->versions[$path])) {
			$version = $this->_getRaw($this->getPrefixed('version:'.$path));
			$this->versions[$path] = $version === false ? null : $version;
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
	private function setVersion($path, $version) {
		$this->_setRaw($this->getPrefixed('version:'.$path), $version, $this->expiration);
		$this->versions[$path] = $version;
	}

	/**
	 * Checks if a key is locked
	 *
	 * @param  string $key  the key to check
	 * @return boolean      true if a lock exists, else false
	 */
	private function hasLock($key) {
		$fullKey = $this->getPrefixed('lock:'.$key);
		$hasLock = $this->hasLocking() ? $this->_lock($key) === false : $this->_isset($fullKey) === true;

		// If we just created an accidental lock, remove it.

		if ($this->hasLocking() && !$hasLock) {
			$this->_unlock($key);
		}

		return $hasLock;
	}

	/**
	 * Wrapper method for getting a value from the cache
	 *
	 * @param  string $key  the element's key
	 * @return mixed        the value or false if it wasn't found
	 */
	abstract protected function _get($key);

	/**
	 * Special wrapper for scalar data
	 *
	 * This wrapper is used when this implementation needs to store simple scalar
	 * values (i.e. version numbers or locks). It exists so that some caching
	 * systems can skip the serialization step when storing data.
	 *
	 * This method is not publicly available.
	 *
	 * @param  string $key  the element's key
	 * @return mixed        the value or false if it wasn't found
	 */
	abstract protected function _getRaw($key);

	/**
	 * Wrapper method for setting a value in the cache
	 *
	 * @param  string $key         the element's key
	 * @param  string $value       the element's value
	 * @param  string $expiration  the expiration time in seconds
	 * @return mixed               the value just set
	 */
	abstract protected function _set($key, $value, $expiration);

	/**
	 * Wrapper method for setting a scalar value in the cache
	 *
	 * This wrapper is used when this implementation needs to read simple scalar
	 * values (i.e. version numbers or locks). It exists so that some caching
	 * systems can skip the deserialization step when reading data.
	 *
	 * This method is not publicly available.
	 *
	 * @param  string $key         the element's key
	 * @param  string $value       the element's value
	 * @param  string $expiration  the expiration time in seconds
	 * @return mixed               the value just set
	 */
	abstract protected function _setRaw($key, $value, $expiration);

	/**
	 * Wrapper method for deleting a value from the cache
	 *
	 * @param  string $key  the element's key
	 * @return boolean      true if the value was deleted, else false
	 */
	abstract protected function _delete($key);

	/**
	 * Wrapper method for testing for existence
	 *
	 * This method exists so that get() can distinguish between 'false' as a
	 * value and 'false' as a sign of missing elements.
	 *
	 * @param  string $key  the element's key
	 * @return boolean      true if the value exists, else false
	 */
	abstract protected function _isset($key);

	/**
	 * Wrapper method for adding 1 to a value
	 *
	 * Since many caching systems directly support incremeting a value, this
	 * wrapper was added. Systems without support should just get the value,
	 * add 1 and store it again.
	 *
	 * @param  string $key  the element's key
	 * @return boolean      true if the increment was successful, else false
	 */
	abstract protected function _increment($key);
}
