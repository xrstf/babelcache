<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @ingroup cache
 */
class BabelCache_Memcache extends BabelCache_Abstract {
	protected $memcached = null;

	public function getMaxKeyLength() {
		return 200; // unbekannt -> Schätzwert
	}

	public function hasLocking() {
		return false;
	}

	public function __construct($host = 'localhost', $port = 11211) {
		global $I18N;

		$this->memcached = new Memcache();

		if (!$this->memcached->connect($host, $port)) {
			throw new BabelCache_Exception($I18N->msg('BabelCache_memcache_error', $host, $port));
		}
	}

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

	protected function _getRaw($key) { return $this->memcached->get($key); }
	protected function _get($key)    { return unserialize($this->memcached->get($key)); }

	protected function _setRaw($key, $value, $expiration) { return $this->memcached->set($key, $value, 0, $expiration); }
	protected function _set($key, $value, $expiration)    { return $this->memcached->set($key, serialize($value), 0, $expiration); }

	protected function _delete($key) { return $this->memcached->delete($key);        }
	protected function _isset($key)  { return $this->memcached->get($key) !== false; }

	protected function _increment($key) {
		return $this->memcached->increment($key) !== false;
	}
}
