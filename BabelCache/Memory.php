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
class BabelCache_Memory extends BabelCache implements BabelCache_Interface {
	protected $data = array();

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
		$namespace = self::cleanupNamespace($namespace);
		$key       = self::cleanupKey($key);

		$this->data[$namespace][$key] = $value;
		return $value;
	}

	public function exists($namespace, $key) {
		$namespace = self::cleanupNamespace($namespace);
		$key       = self::cleanupKey($key);

		// in_array, weil isset(null) = false ist.
		return isset($this->data[$namespace]) && (isset($this->data[$namespace][$key]) || in_array($key, array_keys($this->data[$namespace])));
	}

	public function get($namespace, $key, $default = null) {
		$namespace = self::cleanupNamespace($namespace);
		$key       = self::cleanupKey($key);

		return $this->exists($namespace, $key) ? $this->data[$namespace][$key] : $default;
	}

	public function delete($namespace, $key) {
		$namespace = self::cleanupNamespace($namespace);
		$key       = self::cleanupKey($key);

		unset($this->data[$namespace][$key]);
	}

	public function flush($namespace, $recursive = false) {
		if (empty($this->data)) {
			return true;
		}

		$namespace = self::cleanupNamespace($namespace);
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
