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
 * Base factory
 *
 * This class can be used to create %BabelCache instances. Be aware that you
 * have to subclass it and at least need to implement getCacheDirectory().
 *
 * When caching is globally disabled, the factory will always return the
 * blackhole cache.
 *
 * @author  Christoph Mewes
 * @package BabelCache
 */
abstract class BabelCache_Factory {
	private $instances     = array();  ///< array    list of caching instances
	private $cacheDisabled = false;    ///< boolean

	/**
	 * Disable caching globally
	 *
	 * After this method is called, getCache() will always return the blackhole
	 * cache.
	 */
	public function disableCaching() {
		$this->cacheDisabled = true;
	}

	/**
	 * Enable caching globally
	 *
	 * This method re-enables the caching, so that getCache() works normally
	 * again.
	 */
	public function enableCaching() {
		$this->cacheDisabled = false;
	}

	/**
	 * Create / get cache instance
	 *
	 * This method will create a new cache instance, if none was found. If an
	 * instance is already known, it is returned immediatly.
	 *
	 * This method also initializes the cache by setting certain parameters. To
	 * alter them, override the corresponding methods.
	 *
	 * @throws BabelCache_Exception  if the class was not found or the cache is not available
	 * @param  string $className     the full class name of the cache you want to get
	 * @return BabelCache_Interface  the requested cache instance (singleton)
	 */
	public function getCache($className) {
		if ($this->cacheDisabled) {
			return $this->factory('BabelCache_Blackhole');
		}

		if (!class_exists($className)) {
			throw new BabelCache_Exception('Invalid class given.');
		}

		if (!empty($this->instances[$className])) {
			return $this->instances[$className];
		}

		// check availability

		if (!call_user_func(array($className, 'isAvailable'))) {
			throw new BabelCache_Exception('The chosen cache is not available.');
		}

		switch ($className) {
			case 'BabelCache_Memcache':

				$servers = $this->getMemcacheAddresses();
				$cache   = new $className();

				foreach ($servers as $server) {
					$cache->addServer($server[0], $server[1], $server[2]);
				}

				break;

			case 'BabelCache_Memcached':

				$servers = $this->getMemcacheAddresses();
				// care for a persistent connection
				$cache   = new $className($this->getPrefix());

				foreach ($servers as $server) {
					$cache->addServer($server[0], $server[1], isset($server[2]) ? $server[2] : 1);
				}

				break;

			case 'BabelCache_Filesystem':
			case 'BabelCache_Filesystem_Plain':

				$path  = $this->getCacheDirectory();
				$cache = new $className($path);
				break;

			case 'BabelCache_SQLite':

				$conn  = $this->getSQLiteConnection();
				$cache = new $className($conn);
				break;

			default:
				$cache = new $className();
		}

		if (is_callable(array($cache, 'setPrefix'))) {
			$prefix = $this->getPrefix();
			$cache->setPrefix($prefix);
		}

		$this->instances[$className] = $cache;
		return $cache;
	}

	/**
	 * Return memcache server addresses
	 *
	 * This method should return the memcache server address as a single
	 * array(host, port).
	 *
	 * @return array  array(array(host, port, weight))
	 */
	protected function getMemcacheAddresses() {
		return array(array('localhost', 11211, 1));
	}

	/**
	 * Return caching prefix (only useful for in-memory caches)
	 *
	 * This method should return a unique string that identifies the current
	 * system installation. The prefix will be put in front of all cache keys,
	 * so that multiple installations of the same system can co-exist on the
	 * same machine and share the same XCache.
	 *
	 * Some caching systems can do this themselves, but mostly only based on
	 * the current vhost setting. As many developers often develop multiple
	 * projects on the same vhost, this helps to keep the projects separated.
	 *
	 * @return string  the prefix (can be empty, if you know what you're doing)
	 */
	protected function getPrefix() {
		return '';
	}

	/**
	 * Returns the cache directory
	 *
	 * This method will only be used if you use the filesystem cache. It should
	 * return the absolute path to an already existing directory (CHMOD 777).
	 * Trailing slashes are not important.
	 *
	 * See the TestFactory (inside the tests directory) for an example on how
	 * to implement this method.
	 *
	 * @return string  the absolute path to the cache directory
	 */
	abstract protected function getCacheDirectory();

	/**
	 * Returns the sqlite connection
	 *
	 * @return PDO  the established connection
	 */
	abstract protected function getSQLiteConnection();
}
