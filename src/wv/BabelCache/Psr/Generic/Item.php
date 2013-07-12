<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace wv\BabelCache\Psr\Generic;

use DateTime;
use Psr\Cache\ItemInterface;
use wv\BabelCache\Adapter\AdapterInterface;

/**
 * Basic implementation for PSR's cache item
 *
 * @package BabelCache.Psr
 */
class Item implements ItemInterface {
	protected $key;
	protected $item;
	protected $isHit;
	protected $adapter;

	public function __construct($key, array $item, $isHit, AdapterInterface $adapter) {
		$this->key     = $key;
		$this->item    = $item;
		$this->isHit   = !!$isHit;
		$this->adapter = $adapter;
	}

	/**
	 * Returns the key for the current cache item.
	 *
	 * @return string  the key string for this cache item
	 */
	public function getKey() {
		return $this->key;
	}

	/**
	 * Retrieves the value of the item from the cache associated with this objects key.
	 *
	 * @return mixed  the value corresponding to this cache item's key, or null if not found
	 */
	public function get() {
		return $this->isHit() ? $this->item['payload'] : null;
	}

	/**
	 * Stores a value into the cache.
	 *
	 * The $value argument may be any item that can be serialized by PHP.
	 *
	 * @param  mixed        $value  the serializable value to be stored.
	 * @param  int|DateTime $ttl    ttl in seconds or absolute DateTime object
	 * @return bool                 true if the item was successfully saved, else false
	 */
	public function set($value = null, $ttl = null) {
		$expire = null;

		if (is_int($ttl)) {
			$expire = time() + $ttl;
		}
		elseif ($ttl instanceof DateTime) {
			$expire = $ttl->getTimestamp();
		}

		$this->isHit = true;
		$this->item  = array(
			'payload' => $value,
			'expire'  => $expire
		);

		return $this->adapter->set($this->getKey(), $this->item);
	}

	/**
	 * Confirms if the cache item exists in the cache.
	 *
	 * @return bool  true if the request resulted in a cache hit, false otherwise
	 */
	public function isHit() {
		if ($this->isHit && $this->item['expire'] !== null && $this->item['expire'] <= time()) {
			$this->delete();
		}

		return $this->isHit;
	}

	/**
	 * Ddeletes the current key from the cache.
	 *
	 * @return ItemInterface  the current item
	 */
	public function delete() {
		$this->adapter->delete($this->getKey());

		$this->isHit = false;
		$this->item  = array('payload' => null, 'ttl' => null);

		return $this;
	}
}
