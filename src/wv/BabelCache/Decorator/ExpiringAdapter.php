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

use wv\BabelCache\AdapterInterface;
use wv\BabelCache\Factory;

/**
 * Cache adapter wrapper to generically handle timeouts
 *
 * Since not all implementations provide native support for timeouts, this class
 * puts a layer around another, real cache, adding and handling timeout
 * information transparently to all values.
 *
 * @package BabelCache.Decorator
 */
class ExpiringAdapter implements AdapterInterface {
	protected $adapter;
	protected $ttl;

	const EXPIRE_KEY = '__expire__';
	const VALUE_KEY  = '__value__';

	/**
	 * Constructor
	 *
	 * @param AdapterInterface $realAdapter  the adapter instance to be wrapped
	 * @param int              $ttl          default ttl for all written items
	 */
	public function __construct(AdapterInterface $realAdapter, $ttl) {
		$this->adapter = $realAdapter;
		$this->ttl     = (int) $ttl;
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
	 * Gets a value out of the cache
	 *
	 * This method will try to read the value from the cache.
	 *
	 * @param  string  $key    the object key
	 * @param  boolean $found  will be set to true or false when the method is finished
	 * @return mixed           the found value or null
	 */
	public function get($key, &$found = null) {
		$found = false;
		$data  = $this->adapter->get($key, $found);

		if (!$found) {
			return null;
		}

		$expired = isset($data[self::EXPIRE_KEY]) ? time() > $data[self::EXPIRE_KEY] : false;

		if ($expired) {
			$found = false;

			return null;
		}

		return isset($data[self::VALUE_KEY]) ? $data[self::VALUE_KEY] : $data;
	}

	/**
	 * Sets a value
	 *
	 * This method will put a value into the cache. If it already exists, it
	 * will be overwritten.
	 *
	 * @param  string $key    the object key
	 * @param  mixed  $value  the value to store
	 * @param  mixed  $ttl    timeout in seconds
	 * @return boolean        true on success, else false
	 */
	public function set($key, $value, $ttl = null) {
		$expire = time() + ($ttl === null ? $this->ttl : $ttl);
		$data   = array(self::EXPIRE_KEY => $expire, self::VALUE_KEY => $value);

		return $this->adapter->set($key, $data);
	}

	/**
	 * Checks whether a value exists
	 *
	 * @param  string $key  the object key
	 * @return boolean      true if the value exists, else false
	 */
	public function exists($key) {
		$found = false;

		$this->get($key, $found);

		return $found;
	}

	/**
	 * Deletes a single value from the cache
	 *
	 * @param  string $key  the object key
	 * @return boolean      true if the value was deleted, else false
	 */
	public function delete($key) {
		return $this->adapter->delete($key);
	}

	/**
	 * Deletes all values
	 *
	 * @return boolean  true if the flush was successful, else false
	 */
	public function clear() {
		return $this->adapter->clear();
	}
}
