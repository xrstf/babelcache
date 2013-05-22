<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace wv\BabelCache\Cache;

use wv\BabelCache\CacheInterface;

/**
 * Runtime cache
 *
 * @package BabelCache.Cache
 */
class Memory implements CacheInterface {
	protected $data = array();  ///< array  contains the cached data {key: value, key: value}

	public function set($namespace, $key, $value) {
		$this->data[$namespace][$key] = $value;

		return $value;
	}

	public function get($namespace, $key, $default = null, &$found = null) {
		$found = $this->exists($namespace, $key);

		return $found ? $this->data[$namespace][$key] : $default;
	}

	public function remove($namespace, $key) {
		$exists = $this->exists($namespace, $key);
		unset($this->data[$namespace][$key]);

		return $exists;
	}

	public function exists($namespace, $key) {
		return isset($this->data[$namespace]) && array_key_exists($key, $this->data[$namespace]);
	}

	public function clear($namespace, $recursive = false) {
		if (empty($this->data)) {
			return true;
		}

		unset($this->data[$namespace]);

		if (!$recursive) {
			return true;
		}

		$pattern    = "$namespace*";
		$namespaces = array_keys($this->data);

		foreach ($namespaces as $pkg) {
			if (fnmatch($pattern, $pkg)) {
				unset($this->data[$pkg]);
			}
		}

		return true;
	}

	public function lock($namespace, $key) {
		return true;
	}

	public function unlock($namespace, $key) {
		return true;
	}

	public function waitForLockRelease($namespace, $key, $default = null, $maxWaitTime = 3, $checkInterval = 750) {
		return $this->get($namespace, $key, $default);
	}

	public function setPrefix($prefix) {
		// do nothing
	}
}
