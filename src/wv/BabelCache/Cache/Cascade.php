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
 * Cache cascade
 *
 * This cache combines two other caches. The primary cache is supposed to be
 * fast, the secondary cache is supposed to be the slow one.
 *
 * @package BabelCache.Cache
 */
class Cascade implements CacheInterface {
	protected $primaryCache;
	protected $secondaryCache;

	public function __construct(CacheInterface $primaryCache, CacheInterface $secondaryCache) {
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

		return $value;
	}

	public function remove($namespace, $key) {
		$this->primaryCache->remove($namespace, $key);
		$this->secondaryCache->remove($namespace, $key);
	}

	public function exists($namespace, $key) {
		return $this->primaryCache->exists($namespace, $key) || $this->secondaryCache->exists($namespace, $key);
	}

	public function clear($namespace, $recursive = false) {
		$this->primaryCache->clear($namespace, $recursive);
		$this->secondaryCache->clear($namespace, $recursive);
	}

	public function lock($namespace, $key) {
		return $this->primaryCache->lock($namespace, $key);
	}

	public function unlock($namespace, $key) {
		return $this->primaryCache->unlock($namespace, $key);
	}

	public function waitForLockRelease($namespace, $key, $default = null, $maxWaitTime = 3, $checkInterval = 750) {
		return $this->primaryCache->waitForLockRelease($namespace, $key, $default, $maxWaitTime, $checkInterval);
	}

	public function setPrefix($prefix) {
		$this->primaryCache->setPrefix($prefix);
		$this->secondaryCache->setPrefix($prefix);
	}
}
