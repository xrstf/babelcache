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
 * Memcached wrapper
 *
 * This class wraps the memcached extension of PHP. Don't mix it up with the
 * memcache (without d!) extension, for which you have to use
 * BabelCache_Memcache.
 *
 * @author  Christoph Mewes
 * @see     http://www.php.net/manual/de/book.memcached.php
 * @package BabelCache.Storage
 */
class BabelCache_Memcached extends BabelCache_Memcache {
	/**
	 * Checks whether a caching system is avilable
	 *
	 * @return boolean  true if php_memcached is available, else false
	 */
	public static function isAvailable() {
		return class_exists('Memcached');
	}

	public function addServerEx($host, $port = 11211, $weight = 0, $persistent = true, $timeout = 1, $retryInterval = 15, $status = true, $failureCallback = null) {
		throw new BabelCache_Exception('Extended server configuration is only available in php_memcache.');
	}

	public function addServer($host, $port = 11211, $weight = 0) {
		$servers = $this->memcached->getServerList();
		if (is_array($servers)) {
			foreach ($servers as $server) {
				if($server['host'] == $host and $server['port'] == $port) {
					return true;
                }
			}
		}
		return $this->memcached->addServer($host, $port, $weight);
	}

	public function getMemcachedVersion() {
		$result = $this->memcached->getVersion();
		return empty($result) ? false : reset($result);
	}

	public function getStats() {
		$result = $this->memcached->getStats();
		return empty($result) ? false : reset($result);
	}

	public function __construct($host = 'localhost', $port = 11211) {
		$this->memcached = new Memcached();

		if (!$this->addServer($host, $port)) {
			throw new BabelCache_Exception('Could not connect to Memcached @ '.$host.':'.$port.'!');
		}
	}

	protected function _get($key, $default) {
		$value = $this->memcached->get($key);
		if ($this->memcached->getResultCode() != Memcached::RES_NOTFOUND) {
			return $value;
		}
		else {
			return $default;
		}
	}

	protected function _setRaw($key, $value, $expiration) {
		return $this->memcached->set($key, $value, $expiration);
	}

	protected function _set($key, $value, $expiration) {
		return $this->memcached->set($key, $value, $expiration);
	}

	protected function _isset($key) {
		$this->memcached->get($key);
		return $this->memcached->getResultCode() != Memcached::RES_NOTFOUND;
	}
}
