<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

abstract class BabelCache_Factory {
	private $instances     = array();  ///< array
	private $cacheDisabled = false;    ///< boolean

	public function disableCaching() {
		$this->cacheDisabled = true;
	}

	public function enableCaching() {
		$this->cacheDisabled = false;
	}

	/**
	 * @param  string $cacheName
	 * @return BabelCache_Interface
	 */
	public function getCache($cacheName) {
		if ($this->cacheDisabled) {
			return $this->factory('Blackhole');
		}

		$className = 'BabelCache_'.$cacheName;

		if (!class_exists($className)) {
			throw new BabelCache_Exception('Invalid class given.');
		}

		if (!empty($this->instances[$className])) {
			return $this->instances[$className];
		}

		// check availability

		if (!call_user_func(array($className, 'isAvailable'))) {
			throw new BabelCache_Exception('The chosen cache is not available.');
		}

		switch ($className) {
			case 'BabelCache_APC':
			case 'BabelCache_eAccelerator':
			case 'BabelCache_XCache':
			case 'BabelCache_ZendServer':

				$prefix = $this->getPrefix();
				$cache  = new $className();

				$cache->setPrefix($prefix);
				break;

			case 'BabelCache_Memcache':
			case 'BabelCache_Memcached':

				$address = $this->getMemcacheAddress();
				$prefix  = $this->getPrefix();
				$cache   = new $className($address[0], $address[1]);

				$cache->setPrefix($prefix);
				break;

			case 'BabelCache_Filesystem':

				$path  = $this->getCacheDirectory();
				$cache = new $className($path);
				break;

			default:
				$cache = new $className();
		}

		$this->instances[$className] = $cache;
		return $cache;
	}

	abstract protected function getMemcacheAddress(); // array(host, port)
	abstract protected function getPrefix();          // string
	abstract protected function getCacheDirectory();  // string
}
