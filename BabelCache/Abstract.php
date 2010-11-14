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
abstract class BabelCache_Abstract extends BabelCache implements BabelCache_Interface {
	protected $versions        = array();
	protected $namespacePrefix = 'foo';

	abstract public function getMaxKeyLength();
	abstract public function hasLocking();

	abstract protected function _get($key);
	abstract protected function _getRaw($key);
	abstract protected function _set($key, $value, $expiration);
	abstract protected function _setRaw($key, $value, $expiration);
	abstract protected function _delete($key);
	abstract protected function _isset($key);
	abstract protected function _increment($key);

	protected function getFullKey($namespace, $key) {
		$fullKey = $this->getFullKeyHelper($namespace, $key);
		self::checkKeyLength($this->namespacePrefix.'/'.$fullKey, $this->getMaxKeyLength());
		return $fullKey;
	}

	protected function getVersion($path) {
		if (!isset($this->versions[$path])) {
			$version = $this->_getRaw($this->namespacePrefix.'/version:'.$path);
			$this->versions[$path] = $version === false ? null : $version;
		}

		return $this->versions[$path];
	}

	protected function setVersion($path, $version) {
		$this->_setRaw($this->namespacePrefix.'/version:'.$path, $version, $this->expiration);
		$this->versions[$path] = $version;
	}

	protected function createVersionPath($fullKey, $createIfMissing = true, $excludeLastVersion = false) {
		list ($pathString, $keyName) = explode('$', $fullKey);

		$path  = array();
		$steps = explode('.', $pathString);

		foreach ($steps as $idx => $step) {
			$path[]         = $step;
			$currentPath    = implode('.', $path);
			$currentVersion = $this->getVersion($currentPath);

			if ($currentVersion === null) {
				if ($createIfMissing) {
					$currentVersion = rand(1, 1000);
				}
				else {
					return false;
				}

				$this->setVersion($currentPath, $currentVersion);
			}

			$path[$idx] = $step.'@'.$currentVersion;
		}

		return self::versionPathHelper($path, $keyName, $excludeLastVersion);
	}

	public function lock($namespace, $key, $duration = 1) {
		$key = $this->getFullKey($namespace, $key);

		if ($this->hasLocking()) {
			return $this->_lock($key);
		}
		else {
			$fullKey = $this->namespacePrefix.'/lock:'.$key;
			$isset   = $this->_isset($fullKey);

			if ($isset === true) {
				return false;
			}

			return $this->_setRaw($fullKey, 1, $duration);
		}
	}

	public function unlock($namespace, $key) {
		$key = $this->getFullKey($namespace, $key);

		if ($this->hasLocking()) {
			return $this->_unlock($key);
		}
		else {
			$fullKey = $this->namespacePrefix.'/lock:'.$key;
			$isset   = $this->_isset($fullKey);

			if ($isset === false) {
				return true;
			}

			return $this->_delete($fullKey);
		}
	}

	protected function hasLock($key) {
		$fullKey = $this->namespacePrefix.'/lock:'.$key;
		$hasLock = $this->hasLocking() ? $this->_lock($key) === false : $this->_isset($fullKey) === true;

		// If we just created an accidental lock, remove it.

		if ($this->hasLocking() && !$hasLock) {
			$this->_unlock($key);
		}

		return $hasLock;
	}

	public function waitForObject($namespace, $key, $default = null, $maxWaitTime = 3, $checkInterval = 50) {
		$key            = $this->getFullKey($namespace, $key);
		$start          = microtime(true);
		$waited         = 0;
		$checkInterval *= 1000;

		while ($waited < $maxWaitTime && $this->hasLock($key)) {
			usleep($checkInterval);
			$waited = microtime(true) - $start;
		}

		if (!$this->hasLock($key)) {
			return $this->get($namespace, $key, $default);
		}
		else {
			return $default;
		}
	}

	public function set($namespace, $key, $value) {
		$key  = $this->getFullKey($namespace, $key);
		$path = $this->createVersionPath($key, true);
		$path = $this->namespacePrefix.'/'.$path;


		if (!$this->_set($path, $value, $this->expiration)) {
			throw new BabelCache_Exception('Error setting value @ '.$key.'!');
		}

		return $value;
	}

	public function exists($namespace, $key) {
		$key  = $this->getFullKey($namespace, $key);
		$path = $this->createVersionPath($key, false);

		if ($path !== false) {
			$path = $this->namespacePrefix.'/'.$path;
			$path = $this->_isset($path); // path als Status missbrauchen
		}

		return $path !== false;
	}

	public function get($namespace, $key, $default = null) {
		$key  = $this->getFullKey($namespace, $key);
		$path = $this->createVersionPath($key, false);

		if ($path === false) {
			return $default;
		}

		$path = $this->namespacePrefix.'/'.$path;
		return $this->_isset($path) ? $this->_get($path) : $default;
	}

	public function delete($namespace, $key) {
		$key  = $this->getFullKey($namespace, $key);
		$path = $this->createVersionPath($key, false);
		$path = $this->namespacePrefix.'/'.$path;
		return $this->_delete($path);
	}

	/**
	 * $recursive wird ignoriert und immer auf true gesetzt.
	 */
	public function flush($namespace, $recursive = false) {
		$fullKey = parent::cleanupNamespace($namespace).'$';
		$path    = $this->createVersionPath($fullKey, false, true);

		if ($path === false) {
			return false;
		}

		// Prefix/version:foo@123.bar@45.mynamespace++;

		$this->versions = array();
		return $this->_increment($this->namespacePrefix.'/version:'.$path, 1, $this->expiration) !== false;
	}
}
