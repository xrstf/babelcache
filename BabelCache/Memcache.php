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
 * Memcache wrapper
 *
 * This class wraps the memcache extension of PHP. Don't mix it up with the
 * memcached (with d!) extension, for which you have to use BabelCache_Memcached.
 *
 * @author Christoph Mewes
 * @see    http://www.php.net/manual/de/book.memcache.php
 */
class BabelCache_Memcache extends BabelCache_Abstract {
	protected $memcached = null;  ///< Memcache  the current Memcache instance

	/**
	 * Constructor
	 *
	 * Opens the connection to memcached.
	 *
	 * @throws BabelCache_Exception  if the connection could not be established
	 * @param  string $host          the host
	 * @param  int    $port          the port
	 */
	public function __construct($host = 'localhost', $port = 11211) {
		$this->memcached = new Memcache();

		if (!$this->memcached->connect($host, $port)) {
			throw new BabelCache_Exception('Could not connect to Memcache @ '.$host.':'.$port.'!');
		}
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
	 * This method tries to connect with memcached and set a value.
	 *
	 * @param  string $host  the host
	 * @param  int    $port  the port
	 * @return boolean       true if it worked, else false
	 */
	public static function isAvailable($host = 'localhost', $port = 11211) {
		if (!class_exists('Memcache')) {
			return false;
		}

		$testCache = new Memcache();

		if (!$testCache->connect($host, $port)) {
			return false;
		}

		$available = $testCache->set('test', 1, 0, 1);
		$testCache->close();

		return $available;
	}

	protected function _getRaw($key) {
		return $this->memcached->get($key);
	}

	protected function _get($key) {
		return unserialize($this->memcached->get($key));
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
