<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace wv\BabelCache;

use wv\BabelCache\Cache;
use wv\BabelCache\Psr;

/**
 * Factory
 *
 * @package BabelCache
 */
abstract class Factory {
	/**
	 * list of known adapters
	 *
	 * @var array
	 */
	protected $adapters;

	/**
	 * list of adapters that should not be used together with a generic cache,
	 * but rather use their own, standalone caching implementation for
	 * performance reasons
	 *
	 * @var array
	 */
	protected $overwrites;

	/**
	 * Constructor
	 */
	public function __construct() {
		$prefix = 'wv\BabelCache\Adapter\\';

		$this->adapters = array(
			'apc'        => $prefix.'APC',
			'blackhole'  => $prefix.'Blackhole',
			'filesystem' => $prefix.'Filesystem',
			'memcache'   => $prefix.'Memcache',
			'memcached'  => $prefix.'Memcached',
			'memory'     => $prefix.'Memory',
			'sqlite'     => $prefix.'SQLite',
			'xcache'     => $prefix.'XCache',
			'zendserver' => $prefix.'ZendServer'
		);

		$prefix = 'wv\BabelCache\Cache\\';

		$this->overwrites = array(
			'blackhole'  => $prefix.'Blackhole',
			'memory'     => $prefix.'Memory',
			'filesystem' => $prefix.'Filesystem'
		);
	}

	/**
	 * Set an adapter mapping
	 *
	 * @param string $key        adapter name, e.g. 'sqlite'
	 * @param string $className  class name, e.g. 'my\Cool\Adapter'
	 */
	public function setAdapter($key, $className) {
		$this->adapters[$key] = $className;
	}

	/**
	 * Get a list of all adapters
	 *
	 * @return array  {name: className, name: className, ...}
	 */
	public function getAdapters() {
		return $this->adapters;
	}

	/**
	 * Set an overwrite mapping
	 *
	 * By this, you can make the factory use a concrete caching class instead of
	 * using the generic implementation with a key-value adapter. Use this for
	 * caches like the filesystem which can handle namespaced content natively.
	 *
	 * @param string $key        adapter name, e.g. 'sqlite'
	 * @param string $className  cache class name, e.g. 'my\Cool\SQLiteCache'
	 */
	public function setOverwrite($key, $className) {
		$this->overwrites[$key] = $className;
	}

	/**
	 * Create cache instance
	 *
	 * @throws Exception              if the adapter was not found or is not available
	 * @param  string  $adapter       the adapter key
	 * @param  boolean $forceGeneric  set this to true to disable the automatic class overwriting
	 * @return CacheInterface         a fresh cache instance
	 */
	public function getCache($adapter, $forceGeneric = false) {
		$className   = $this->getAdapterClass($adapter);
		$overwritten = false;

		// overwrite the adapter with a custom caching implementation

		if (!$forceGeneric && isset($this->overwrites[$adapter])) {
			$className   = $this->overwrites[$adapter];
			$overwritten = true;
		}

		// create adapter/cache (assuming both have the same signatures)

		$instance = $this->construct($adapter, $className);
		$cache    = $overwritten ? $instance : new Cache\Generic($instance);

		$instance->setPrefix($this->getPrefix());

		// done

		return $instance;
	}

	/**
	 * Create a PSR-compatible cache instance
	 *
	 * To use this, you must have the actual PSR interfaces in your project.
	 * They are not part of BabelCache, as they are not yet released or finished.
	 *
	 * @throws Exception                 if the adapter was not found or is not available
	 * @param  string $adapter           the adapter key
	 * @return Psr\Cache\CacheInterface  a fresh cache instance
	 */
	public function getPsrCache($adapter) {
		$className = $this->getAdapterClass($adapter);
		$adapter   = $this->construct($adapter, $className);
		$cache     = new Psr\Cache($adapter);

		return $cache;
	}

	/**
	 * Construct an adapter or cache
	 *
	 * This method is used to instantiates adapters. It can also be used to
	 * construct concrete overwrite caches (e.g. for the filesystem), assuming
	 * the adapter and the matching caching implementation share the same
	 * construction dance moves.
	 *
	 * @param  string $name       cache name, e.g. 'sqlite'
	 * @param  string $className  class name, e.h. 'wv\BabelCache\Adapter\SQLite'
	 * @return mixed              the constructed object (instance of $className)
	 */
	protected function construct($name, $className) {
		switch ($name) {
			case 'memcache':

				$servers  = $this->getMemcacheAddresses();
				$instance = new $className();

				foreach ($servers as $server) {
					$instance->addServer($server[0], $server[1], isset($server[2]) ? $server[2] : 1);
				}

				break;

			case 'memcached':

				$servers  = $this->getMemcacheAddresses();
				$instance = new $className($this->getPrefix()); // care for a persistent connection

				foreach ($servers as $server) {
					$instance->addServer($server[0], $server[1], isset($server[2]) ? $server[2] : 1);
				}

				break;

			case 'filesystem':

				$path     = $this->getCacheDirectory();
				$instance = new $className($path);

				break;

			case 'sqlite':

				$conn     = $this->getSQLiteConnection();
				$instance = new $className($conn);

				break;

			default:
				$instance = new $className();
		}

		return $instance;
	}

	/**
	 * Return the class name for an adapter
	 *
	 * @param  string $adapter  adapter name
	 * @return string
	 */
	protected function getAdapterClass($adapter) {
		if (!isset($this->adapters[$adapter])) {
			throw new Exception('The selected cache adapter "'.$adapter.'" does not exist.');
		}

		$className = $this->adapters[$adapter];

		// check availability

		if (!call_user_func(array($className, 'isAvailable'))) {
			throw new Exception('The "'.$adapter.'" adapter is not available.');
		}

		return $className;
	}

	/**
	 * Return memcache server addresses
	 *
	 * This method should return a list of servers, each one being a tripel of
	 * [host, port, weight].
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
