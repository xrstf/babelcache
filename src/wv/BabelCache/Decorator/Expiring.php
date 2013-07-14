<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace wv\BabelCache\Decorator;

use wv\BabelCache\CacheInterface;
use wv\BabelCache\Factory;

/**
 * Cache wrapper to generically handle timeouts
 *
 * Since not all implementations provide native support for timeouts, this class
 * puts a layer around another, real cache, adding and handling timeout
 * information transparently to all values.
 *
 * @package BabelCache.Decorator
 */
class Expiring extends Base implements CacheInterface {
	protected $ttl;

	const EXPIRE_KEY = '__expire__';
	const VALUE_KEY  = '__value__';

	/**
	 * Constructor
	 *
	 * @param CacheInterface $realCache  the caching instance to be wrapped
	 * @param int            $ttl        default ttl for all written items
	 */
	public function __construct(CacheInterface $realCache, $ttl) {
		parent::__construct($realCache);

		$this->ttl = (int) $ttl;
	}

	/**
	 * Checks whether a caching system is avilable
	 *
	 * This method will be called before an instance is created. It is supposed
	 * to check for the required functions and whether user data caching is
	 * enabled.
	 *
	 * @param  Factory $factory  the project's factory to give the adapter some more knowledge
	 * @return boolean           true if the cache can be used, else false
	 */
	public static function isAvailable(Factory $factory = null) {
		return true;
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
	public function set($namespace, $key, $value, $ttl = null) {
		$expire = time() + ($ttl === null ? $this->ttl : $ttl);
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
	public function get($namespace, $key, $default = null, &$found = null) {
		$found = false;
		$data  = $this->cache->get($namespace, $key, null, $found);

		if (!$found) {
			return $default;
		}

		$expired = isset($data[self::EXPIRE_KEY]) ? time() > $data[self::EXPIRE_KEY] : false;

		if ($expired) {
			$found = false;

			return $default;
		}

		return isset($data[self::VALUE_KEY]) ? $data[self::VALUE_KEY] : $data;
	}

	/**
	 * Checks whether a value exists
	 *
	 * @param  string $namespace  the namespace to use
	 * @param  string $key        the object key
	 * @return boolean            true if the value exists, else false
	 */
	public function exists($namespace, $key) {
		$found = false;

		$this->get($namespace, $key, null, $found);

		return $found;
	}
}
