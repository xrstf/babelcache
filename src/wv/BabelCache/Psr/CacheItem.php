<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace wv\BabelCache\Psr;

use Psr\Cache\CacheItemInterface;

/**
 * Basic implementation for PSR's cache item
 *
 * @package BabelCache.Psr
 */
class CacheItem implements CacheItemInterface {
	protected $key;
	protected $value;
	protected $isHit;

	public function __construct($key, $value, $isHit) {
		$this->key   = $key;
		$this->value = $value;
		$this->isHit = !!$isHit;
	}

	public function getKey() {
		return $this->key;
	}

	public function getValue() {
		return $this->value;
	}

	public function setValue($value) {
		$this->value = $value;
	}

	public function isHit() {
		return $this->isHit;
	}
}
