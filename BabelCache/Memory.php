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
class BabelCache_Memory extends BabelCache implements BabelCache_ISeekable {
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
		$namespace = parent::cleanupNamespace($namespace);
		$key       = parent::cleanupKey($key);

		$this->data[$namespace][$key] = $value;
		return $value;
	}

	public function exists($namespace, $key) {
		$namespace = parent::cleanupNamespace($namespace);
		$key       = parent::cleanupKey($key);

		// in_array, weil isset(null) = false ist.
		return isset($this->data[$namespace]) && (isset($this->data[$namespace][$key]) || in_array($key, array_keys($this->data[$namespace])));
	}

	public function get($namespace, $key, $default = null) {
		$namespace = parent::cleanupNamespace($namespace);
		$key       = parent::cleanupKey($key);

		return $this->exists($namespace, $key) ? $this->data[$namespace][$key] : $default;
	}

	public function find($namespace, $key = '*', $getKey = false, $recursive = false) {
		$namespace = parent::cleanupNamespace($namespace);
		$key       = parent::cleanupKey($key);
		$pattern   = $recursive ? "$namespace*/$key" : "$namespace/$key";

		foreach ($this->data as $namespace => &$elements) {
			foreach ($elements as $k => $value) {
				$keyToMatch = "$namespace/$k";
				if (fnmatch($pattern, $keyToMatch)) return $getKey ? $k : $value;
			}
		}

		return null;
	}

	public function delete($namespace, $key) {
		$namespace = parent::cleanupNamespace($namespace);
		$key       = parent::cleanupKey($key);

		unset($this->data[$namespace][$key]);
	}

	public function flush($namespace, $recursive = false) {
		if (empty($this->data)) {
			return true;
		}

		$namespace = parent::cleanupNamespace($namespace);
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

	public function getAll($namespace, $recursive = false) {
		$namespace = parent::cleanupNamespace($namespace);

		if (!$recursive) {
			return isset($this->data[$namespace]) ? $this->data[$namespace] : array();
		}

		$pattern = "$namespace*";
		$return  = array();

		foreach ($this->data as $pkg => &$elements) {
			if (fnmatch($pattern, $pkg)) {
				$return = array_merge($return, $elements);
			}
		}

		return $return;
	}

	public function getElementCount($namespace, $recursive = false) {
		$namespace = parent::cleanupNamespace($namespace);

		if (!$recursive) {
			return isset($this->data[$namespace]) ? count($this->data[$namespace]) : 0;
		}

		$pattern = "$namespace*";
		$count   = 0;

		foreach ($this->data as $pkg => &$elements) {
			if (fnmatch($pattern, $pkg)) {
				$count += count($elements);
			}
		}

		return $count;
	}

	public function getSize($namespace, $recursive = false) {
		$namespace = parent::cleanupNamespace($namespace);
		$pattern   = $recursive ? "$namespace*" : $namespace;
		$size      = 0;

		foreach ($this->data as $pkg => &$elements) {
			if (fnmatch($pattern, $pkg)) {
				foreach ($elements as $key => &$value) {
					$size += strlen($key) + strlen(serialize($value));
				}
			}
		}

		return $size;
	}
}
