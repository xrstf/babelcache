<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace wv\BabelCache\Adapter;

use wv\BabelCache\AdapterInterface;
use wv\BabelCache\IncrementInterface;

/**
 * XCache
 *
 * This class wraps the XCache extension, which provides both opcode and vardata
 * caching.
 *
 * @see     http://xcache.lighttpd.net/
 * @package BabelCache.Adapter
 */
class XCache implements AdapterInterface, IncrementInterface {
	public static function isAvailable() {
		// XCache will throw a warning if it is misconfigured. We don't want to see that one.
		return function_exists('xcache_set') && @xcache_set('test', 1, 1);
	}

	public function get($key, &$found = null) {
		$found = xcache_isset($key);

		return $found ? unserialize(xcache_get($key)) : null;
	}

	public function set($key, $value, $expiration = null) {
		return xcache_set($key, serialize($value), $expiration);
	}

	public function remove($key) {
		return xcache_unset($key);
	}

	public function exists($key) {
		return xcache_isset($key);
	}

	public function clear() {
		xcache_clear_cache(XC_TYPE_PHP, 0);

		return true;
	}

	public function increment($key) {
		return xcache_inc($key);
	}
}
