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
 * Blackhole Caching
 *
 * This cache does not save any data at all and exists as the logical pendant
 * to all the other, real systems. It should be used if the caching is disabled,
 * so that code relying on a cache can use the cache instance normally without
 * checking for null.
 *
 * @package BabelCache.Cache
 */
class Blackhole implements CacheInterface {
	/**
	 * Sets the key prefix
	 *
	 * @param string $prefix  the prefix to use (will be trimmed)
	 */
	public function setPrefix($prefix) {
		// do nothing
	}

	public function get($namespace, $key, $default = null, &$found = null) {
		$found = false;

		return $default;
	}

	public function set($namespace, $key, $value) {
		return $value;
	}

	public function remove($namespace, $key) {
		return true;
	}

	public function exists($namespace, $key) {
		return false;
	}

	public function clear($namespace, $recursive = false) {
		return true;
	}

	public function lock($namespace, $key) {
		return true;
	}

	public function unlock($namespace, $key) {
		return true;
	}

	public function waitForLockRelease($namespace, $key, $default = null, $maxWaitTime = 3, $checkInterval = 750) {
		return $default;
	}
}
