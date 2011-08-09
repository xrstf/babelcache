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
 * Memcached wrapper
 *
 * This class wraps the memcached extension of PHP. Don't mix it up with the
 * memcache (without d!) extension, for which you have to use
 * BabelCache_Memcache.
 *
 * @author Christoph Mewes
 * @see    http://www.php.net/manual/de/book.memcached.php
 */
class BabelCache_Memcached extends BabelCache_Memcache {
	public static function isAvailable($host = 'localhost', $port = 11211) {
		if (!class_exists('Memcached')) {
			return false;
		}

		$testCache = new Memcached();

		if (!$testCache->addServer($host, $port)) {
			return false;
		}

		$available = $testCache->set('test', 1, 1);
		$testCache = null;

		return $available;
	}

	public function __construct($host = 'localhost', $port = 11211) {
		$this->memcached = new Memcached();

		if (!$this->memcached->addServer($host, $port)) {
			throw new BabelCache_Exception('Could not connect to Memcached @ '.$host.':'.$port.'!');
		}
	}

	protected function _getRaw($key) {
		return $this->memcached->get($key);
	}

	protected function _get($key) {
		return unserialize($this->memcached->get($key));
	}

	protected function _setRaw($key, $value, $expiration) {
		return $this->memcached->set($key, $value, $expiration);
	}

	protected function _set($key, $value, $expiration) {
		return $this->memcached->set($key, serialize($value), $expiration);
	}

	protected function _delete($key) {
		return $this->memcached->delete($key);
	}

	protected function _isset($key) {
		$this->memcached->get($key);
		return $this->memcached->getResultCode() != Memcached::RES_NOTFOUND;
	}

	protected function _increment($key) {
		return $this->memcached->increment($key) !== false;
	}
}
