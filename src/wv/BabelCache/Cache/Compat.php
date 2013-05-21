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
 * Compatibility layer
 *
 * Wrap a BabelCache 2.x cache in this decorator to get the old 1.x methods
 * back.
 *
 * @package BabelCache.Cache
 */
class Compat implements CacheInterface {
	protected $cache; ///< CacheInterface  the wrapped caching instance

	/**
	 * Constructor
	 *
	 * @param CacheInterface $realCache  the caching instance to be wrapped
	 */
	public function __construct(CacheInterface $realCache) {
		$this->cache = $realCache;
	}

	/* 1.x interface */

	public function delete($namespace, $key) {
		return $this->cache->remove($namespace, $key);
	}

	public function flush($namespace, $recursive = false) {
		return $this->cache->clear($namespace, $recursive);
	}

	public function waitForObject($namespace, $key, $default = null, $maxWaitTime = 3, $checkInterval = 50) {
		return $this->cache->waitForLockRelease($namespace, $key, $default, $maxWaitTime, $checkInterval);
	}

	/* 1:1 proxies for the 2.x interface */

	public function set($namespace, $key, $value) {
		return $this->cache->set($namespace, $key, $data);
	}

	public function get($namespace, $key, $default = null, &$found = null) {
		return $this->cache->set($namespace, $key, $default, $found);
	}

	public function remove($namespace, $key) {
		return $this->cache->remove($namespace, $key);
	}

	public function exists($namespace, $key) {
		return $this->cache->exists($namespace, $key);
	}

	public function clear($namespace, $recursive = false) {
		return $this->cache->clear($namespace, $recursive);
	}

	public function lock($namespace, $key) {
		return $this->cache->lock($namespace, $key);
	}

	public function unlock($namespace, $key) {
		return $this->cache->unlock($namespace, $key);
	}

	public function waitForLockRelease($namespace, $key, $default = null, $maxWaitTime = 3, $checkInterval = 750) {
		return $this->cache->waitForLockRelease($namespace, $key, $default, $maxWaitTime, $checkInterval);
	}

	public function setPrefix($prefix) {
		return $this->cache->setPrefix($prefix);
	}
}
