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
			'apc'           => $prefix.'APC',
			'blackhole'     => $prefix.'Blackhole',
			'filesystem'    => $prefix.'Filesystem',
			'memcache'      => $prefix.'Memcache',
			'memcached'     => $prefix.'Memcached',
			'memcachedsasl' => $prefix.'MemcachedSASL',
			'memory'        => $prefix.'Memory',
			'mysql'         => $prefix.'MySQL',
			'redis'         => $prefix.'Redis',
			'sqlite'        => $prefix.'SQLite',
			'xcache'        => $prefix.'XCache',
			'zendserver'    => $prefix.'ZendServer'
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
		if ($className === null) {
			unset($this->adapters[$key]);
		}
		else {
			$this->adapters[$key] = $className;
		}
	}

	/**
	 * Get a list of all adapters
	 *
	 * @return array  {name: className, name: className, ...} or [name, name, ...]
	 */
	public function getAdapters($keysOnly = false) {
		return $keysOnly ? array_keys($this->adapters) : $this->adapters;
	}

	/**
	 * Get a list of all adapters
	 *
	 * @return array  {name: className, name: className, ...} or [name, name, ...]
	 */
	public function getAvailableAdapters($keysOnly = false) {
		$result = array();

		foreach ($this->getAdapters(false) as $name => $className) {
			if (call_user_func(array($className, 'isAvailable'), $this)) {
				if ($keysOnly) {
					$result[] = $name;
				}
				else {
					$result[$name] = $className;
				}
			}
		}

		return $result;
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
		if ($className === null) {
			unset($this->overwrites[$key]);
		}
		else {
			$this->overwrites[$key] = $className;
		}
	}

	/**
	 * Create an adapter
	 *
	 * @throws Exception         if the adapter was not found or is not available
	 * @param  string $adapter   the adapter key
	 * @return AdapterInterface  a fresh adapter instance
	 */
	public function getAdapter($adapter) {
		$className = $this->getAdapterClass($adapter);
		$adapter   = $this->construct($adapter, $className);

		return $adapter;
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

		$cache->setPrefix($this->getPrefix());

		// done

		return $cache;
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
		// We can't test this yet...
		// @codeCoverageIgnoreStart
		$adapter = $this->getAdapter($adapter);
		$cache   = new Psr\Cache($adapter);

		return $cache;
		// @codeCoverageIgnoreEnd
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
			case 'memcache':      $instance = $this->constructMemcache($className);      break;
			case 'memcached':     $instance = $this->constructMemcached($className);     break;
			case 'memcachedsasl': $instance = $this->constructMemcachedSASL($className); break;
			case 'filesystem':    $instance = $this->constructFilesystem($className);    break;
			case 'sqlite':        $instance = $this->constructSQLite($className);        break;
			case 'mysql':         $instance = $this->constructMySQL($className);         break;
			case 'redis':         $instance = $this->constructRedis($className);         break;
			default:              $instance = new $className();
		}

		return $instance;
	}

	protected function constructMemcache($className) {
		$servers = $this->getMemcachedAddresses();

		if (empty($servers)) {
			throw new Exception('No memcached servers have been returned from getMemcachedAddresses()!');
		}

		// @codeCoverageIgnoreStart
		$instance = new $className();

		foreach ($servers as $server) {
			$instance->addServer($server[0], $server[1], isset($server[2]) ? $server[2] : 1);
		}

		return $instance;
		// @codeCoverageIgnoreEnd
	}

	protected function constructMemcached($className) {
		$servers = $this->getMemcachedAddresses();

		if (empty($servers)) {
			throw new Exception('No memcached servers have been returned from getMemcachedAddresses()!');
		}

		// @codeCoverageIgnoreStart
		$instance = new $className($this->getPrefix()); // care for a persistent connection

		foreach ($servers as $server) {
			$instance->addServer($server[0], $server[1], isset($server[2]) ? $server[2] : 1);
		}

		return $instance;
		// @codeCoverageIgnoreEnd
	}

	protected function constructMemcachedSASL($className) {
		$servers = $this->getMemcachedAddresses();

		if (empty($servers)) {
			throw new Exception('No memcached servers have been returned from getMemcachedAddresses()!');
		}

		// @codeCoverageIgnoreStart
		$server   = reset($servers);
		$auth     = $this->getMemcachedAuthentication();
		$instance = new $className($server[0], $server[1]);

		$instance->authenticate($auth[0], $auth[1]);

		return $instance;
		// @codeCoverageIgnoreEnd
	}

	protected function constructFilesystem($className) {
		$path     = $this->getCacheDirectory();
		$instance = new $className($path);

		return $instance;
	}

	protected function constructSQLite($className) {
		$conn     = $this->getSQLiteConnection();
		$table    = $this->getSQLiteTableName();
		$instance = new $className($conn, $table);

		return $instance;
	}

	protected function constructMySQL($className) {
		$conn     = $this->getMySQLConnection();
		$table    = $this->getMySQLTableName();
		$instance = new $className($conn, $table);

		return $instance;
	}

	protected function constructRedis($className) {
		$servers = $this->getRedisAddresses();

		if (empty($servers)) {
			throw new Exception('No Redis servers have been returned from getRedisAddresses()!');
		}

		// @codeCoverageIgnoreStart
		$client   = new \Predis\Client($servers);
		$instance = new $className($client);

		return $instance;
		// @codeCoverageIgnoreEnd
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

		if (!call_user_func(array($className, 'isAvailable'), $this)) {
			throw new Exception('The "'.$adapter.'" adapter is not available.');
		}

		return $className;
	}

	/**
	 * Return memcached server addresses
	 *
	 * This method should return a list of servers, each one being a tripel of
	 * [host, port, weight].
	 *
	 * @return array  array(array(host, port, weight))
	 */
	public function getMemcachedAddresses() {
		return null;
	}

	/**
	 * Return memcached SASL auth data
	 *
	 * This method should return a tupel, consisting of the username and the
	 * password for the memcached daemon. If this method returns null, it's
	 * assumed no auth is available/needed and the MemcachedSASL adapter is
	 * disabled.
	 *
	 * @return mixed  array(username, password) or null to disable SASL support
	 */
	public function getMemcachedAuthentication() {
		return null;
	}

	/**
	 * Return Redis server addresses
	 *
	 * See https://github.com/nrk/predis#connecting-to-redis for more info on
	 * what shape the address list can take. Return null to disable the Redis
	 * adapter.
	 *
	 * @return array  [{host: ..., port: ...}] or null
	 */
	public function getRedisAddresses() {
		return null;
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
	public function getPrefix() {
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
	abstract public function getCacheDirectory();

	/**
	 * Returns the sqlite connection
	 *
	 * @return PDO  the established connection
	 */
	abstract public function getSQLiteConnection();

	/**
	 * Returns the sqlite table name
	 *
	 * @return string
	 */
	abstract public function getSQLiteTableName();

	/**
	 * Returns the MySQL connection
	 *
	 * @return PDO  the established connection
	 */
	abstract public function getMySQLConnection();

	/**
	 * Returns the MySQL table name
	 *
	 * @return string
	 */
	abstract public function getMySQLTableName();
}
