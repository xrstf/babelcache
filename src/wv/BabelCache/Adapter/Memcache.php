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
 * Memcache wrapper
 *
 * This class wraps the memcache extension of PHP. Don't mix it up with the
 * memcached (with d!) extension, for which you have to use BabelCache_Memcached.
 *
 * @author  Christoph Mewes
 * @see     http://www.php.net/manual/de/book.memcache.php
 * @package BabelCache.Storage
 */
class BabelCache_Memcache extends BabelCache_Abstract {
	protected $memcached = null;  ///< Memcache  the current Memcache instance

	public function __construct() {
		$this->memcached = new Memcache();
	}

	public function getMemcached() {
		return $this->memcached;
	}

	/**
	 *
	 * @param string $host           the port
	 * @param int    $port           the port
	 * @param int    $weight         weight as integer / decides ammount of keys saved on this server
	 * @throws BabelCache_Exception  if the connection could not be established
	 */
	public function addServer($host, $port = 11211, $weight = 0) {
		if (!$this->memcached->addServer($host, $port, true, $weight)) {
			throw new BabelCache_Exception('Could not connect to Memcache @ '.$host.':'.$port.'!');
		}
	}

	public function addServerEx($host, $port = 11211, $weight = 0, $persistent = true, $timeout = 1, $retryInterval = 15, $status = true, $failureCallback = null) {
		return $this->memcached->addServer($host, $port, $persistent, $weight, $timeout, $retryInterval, $status, $failureCallback);
	}

	public function getMemcachedVersion() {
		return $this->memcached->getVersion();
	}

	public function getStats() {
		return $this->memcached->getStats();
	}

	public function getMaxKeyLength() {
		return 200; // unbekannt -> Schätzwert
	}

	public function hasLocking() {
		return false;
	}

	/**
	 * Checks whether a caching system is avilable
	 *
	 * @return boolean  true if php_memcache is available, else false
	 */
	public static function isAvailable() {
		return class_exists('Memcache');
	}

	protected function _getRaw($key) {
		return $this->memcached->get($key);
	}

	protected function _get($key, &$found) {
		$value = $this->memcached->get($key);
		$found = $value !== false;

		return $found ? unserialize($value) : $value;
	}

	protected function _setRaw($key, $value, $expiration) {
		return $this->memcached->set($key, $value, 0, $expiration);
	}

	protected function _set($key, $value, $expiration) {
		return $this->memcached->set($key, serialize($value), 0, $expiration);
	}

	protected function _delete($key) {
		return $this->memcached->delete($key);
	}

	protected function _isset($key) {
		return $this->memcached->get($key) !== false;
	}

	protected function _increment($key) {
		return $this->memcached->increment($key) !== false;
	}
}
