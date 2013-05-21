<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
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
 * @see     http://www.php.net/manual/de/book.memcached.php
 * @package BabelCache.Adapter
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
		if (!$this->memcached->addServer($host, $port, $weight)) {
			throw new BabelCache_Exception('Could not connect to Memcached @ '.$host.':'.$port.'!');
		}
	}

	public function getMemcachedVersion() {
		$result = $this->memcached->getVersion();
		return empty($result) ? false : reset($result);
	}

	public function getStats() {
		$result = $this->memcached->getStats();
		return empty($result) ? false : reset($result);
	}

	public function __construct($persistent_id = null) {
		$this->memcached = new Memcached($persistent_id);
	}

	protected function _get($key, &$found) {
		$value = $this->memcached->get($key);
		$found = $this->memcached->getResultCode() != Memcached::RES_NOTFOUND;

		return $value;
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