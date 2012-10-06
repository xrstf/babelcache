<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * Cache wrapper to generically handle timeouts
 *
 * Since not all implementations provide native support for timeouts, this class
 * puts a layer around another, real cache, adding and handling timeout
 * information transparently to all values.
 *
 * @author  Christoph Mewes
 * @package BabelCache.Storage
 */
class BabelCache_Expiring {
	protected $cache = null; ///< BabelCache_Interface  the wrapped caching instance

	const EXPIRE_KEY = '__expire__';
	const VALUE_KEY  = '__value__';

	/**
	 * Constructor
	 *
	 * @param BabelCache_Interface $realCache  the caching instance to be wrapped
	 */
	public function __construct(BabelCache_Interface $realCache) {
		$this->cache = $realCache;
	}

	/**
	 * Sets a value
	 *
	 * This method will put a value into the cache. If it already exists, it
	 * will be overwritten.
	 *
	 * @param  string $namespace  namespace to use
	 * @param  string $key        object key
	 * @param  mixed  $value      value to store
	 * @param  mixed  $ttl        timeout in seconds
	 * @return mixed              the set value
	 */
	public function set($namespace, $key, $value, $timeout) {
		$expire = time() + $timeout;
		$data   = array(self::EXPIRE_KEY => $expire, self::VALUE_KEY => $value);

		return $this->cache->set($namespace, $key, $data);
	}

	/**
	 * Gets a value out of the cache
	 *
	 * This method will try to read the value from the cache. If it's not found,
	 * $default will be returned.
	 *
	 * @param  string $namespace  the namespace to use
	 * @param  string $key        the object key
	 * @param  mixed  $default    the default value
	 * @return mixed              the found value if not expired or $default
	 */
	public function get($namespace, $key, $default = null) {
		if (!$this->cache->exists($namespace, $key)) {
			return $default;
		}

		$data    = $this->cache->get($namespace, $key);
		$expired = isset($data[self::EXPIRE_KEY]) ? time() > $data[self::EXPIRE_KEY] : false;

		if ($expired) {
			return $default;
		}

		return isset($data[self::VALUE_KEY]) ? $data[self::VALUE_KEY] : $data;
	}
}
