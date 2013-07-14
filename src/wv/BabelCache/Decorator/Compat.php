<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace wv\BabelCache\Decorator;

use wv\BabelCache\CacheInterface;
use wv\BabelCache\Factory;
use wv\BabelCache\Util;

/**
 * Compatibility layer
 *
 * Wrap a BabelCache 2.x cache in this decorator to get the old 1.x methods
 * back.
 *
 * @package BabelCache.Decorator
 */
class Compat extends Base implements CacheInterface {
	/**
	 * Checks whether a caching system is avilable
	 *
	 * This method will be called before an instance is created. It is supposed
	 * to check for the required functions and whether user data caching is
	 * enabled.
	 *
	 * @param  Factory $factory  the project's factory to give the adapter some more knowledge
	 * @return boolean           true if the cache can be used, else false
	 */
	public static function isAvailable(Factory $factory = null) {
		return true;
	}

	/* 1.x interface */

	public function flush($namespace, $recursive = false) {
		return $this->cache->clear($namespace, $recursive);
	}

	public function waitForObject($namespace, $key, $default = null, $maxWaitTime = 3, $checkInterval = 50) {
		return Util::waitForLockRelease($this->cache, $namespace, $key, $default, $maxWaitTime, $checkInterval);
	}
}
