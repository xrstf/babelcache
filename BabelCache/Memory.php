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
 * Runtime cache
 *
 * This class will store the cached data only for the current request. Its main
 * purpose is to aid the filesystem cache in storing the data in memory, so that
 * repeated calls don't have to read and deserialize the data from disk every
 * time.
 *
 * Lockig does of course not make any sense, so the corresponding methods will
 * just return true.
 *
 * @author Christoph Mewes
 * @see    BabelCache_Filesystem
 */
class BabelCache_Memory extends BabelCache implements BabelCache_Interface {
	protected $data = array();  ///< array  contains the cached data {namespace => {key: value, key: value}}

	public static function isAvailable() {
		return true;
	}

	public function lock($namespace, $key, $duration = 1) {
		return true;
	}

	public function unlock($namespace, $key) {
		return true;
	}

	public function waitForObject($namespace, $key, $default = null, $maxWaitTime = 3, $checkInterval = 50) {
		return $this->get($namespace, $key, $default);
	}

	public function set($namespace, $key, $value) {
		$this->data[$namespace][$key] = $value;
		return $value;
	}

	public function exists($namespace, $key) {
		// in_array, weil isset(null) = false ist.
		return isset($this->data[$namespace]) && (isset($this->data[$namespace][$key]) || in_array($key, array_keys($this->data[$namespace])));
	}

	public function get($namespace, $key, $default = null) {
		return $this->exists($namespace, $key) ? $this->data[$namespace][$key] : $default;
	}

	public function delete($namespace, $key) {
		unset($this->data[$namespace][$key]);
	}

	public function flush($namespace, $recursive = false) {
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
}
