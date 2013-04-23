<?php


class BabelCache_Cascade extends Babelcache implements BabelCache_Interface {
	protected $primaryCache;
	protected $secondaryCache;

	public static function isAvailable() {
		return false;
	}

	public function __construct(BabelCache_Interface $primaryCache, BabelCache_Interface $secondaryCache) {
		$this->primaryCache   = $primaryCache;
		$this->secondaryCache = $secondaryCache;
	}

	public function get($namespace, $key, $default = null, &$found = null) {
		$value = $this->primaryCache->get($namespace, $key, $default, $found);
		if (!$found) {
			$value = $this->secondaryCache->get($namespace, $key, $default, $found);
			if ($found) {
				$this->primaryCache->set($namespace, $key, $value);
			}
		}

		return $value;
	}

	public function set($namespace, $key, $value) {
		$this->primaryCache->set($namespace, $key, $value);
		$this->secondaryCache->set($namespace, $key, $value);
	}

	public function delete($namespace, $key) {
		$this->primaryCache->delete($namespace, $key);
		$this->secondaryCache->delete($namespace, $key);
	}

	public function exists($namespace, $key) {
		return $this->primaryCache->exists($namespace, $key) || $this->secondaryCache->exists($namespace, $key);
	}

	public function waitForObject($namespace, $key, $default = null, $maxWaitTime = 3, $checkInterval = 50) {
		return $this->primaryCache->waitForObject($namespace, $key, $default, $maxWaitTime, $checkInterval);
	}

	public function lock($namespace, $key, $duration = 1) {
		return $this->primaryCache->lock($namespace, $key, $duration);
	}

	public function unlock($namespace, $key) {
		return $this->primaryCache->unlock($namespace, $key);
	}

	public function flush($namespace, $recursive = false) {
		$this->primaryCache->flush($namespace, $recursive);
		$this->secondaryCache->flush($namespace, $recursive);
	}

}
