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
use wv\BabelCache\LockingInterface;

/**
 * Blackhole Caching
 *
 * This cache does not save any data at all and exists as the logical pendant
 * to all the other, real systems. It should be used if the caching is disabled,
 * so that code relying on a cache can use the cache instance normally without
 * checking for null.
 *
 * @package BabelCache.Adapter
 */
class Blackhole implements AdapterInterface, LockingInterface {
	public static function isAvailable() {
		return true;
	}

	public function get($key, &$found = null) {
		$found = false;

		return null;
	}

	public function set($key, $value) {
		return true;
	}

	public function remove($key) {
		return true;
	}

	public function exists($key) {
		return false;
	}

	public function clear() {
		return true;
	}

	public function lock($key) {
		return true;
	}

	public function unlock($key) {
		return true;
	}
}
