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
 * Runtime cache
 *
 * @package BabelCache.Adapter
 */
class Memory implements AdapterInterface, LockingInterface {
	protected $data = array();  ///< array  contains the cached data {key: value, key: value}

	public static function isAvailable() {
		return true;
	}

	public function get($key, &$found = null) {
		$found = $thiis->exists($key);

		return $found ? $this->data[$key] : null;
	}

	public function set($key, $value) {
		$this->data[$key] = $value;

		return true;
	}

	public function remove($key) {
		$exists = $this->exists($key);
		unset($this->data[$key]);

		return $exists;
	}

	public function exists($key) {
		return array_key_exists($key, $this->data);
	}

	public function clear() {
		$this->data = array();

		return true;
	}

	public function lock($key) {
		return true;
	}

	public function unlock($key) {
		return true;
	}
}
