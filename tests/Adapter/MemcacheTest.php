<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

use wv\BabelCache\Adapter\Memcache;

class Adapter_MemcacheTest extends Adapter_BaseTest {
	protected function getAdapter() {
		$factory = new TestFactory('fsadapter');

		if (!Memcache::isAvailable($factory)) {
			$this->markTestSkipped('Memcache is not available.');
		}

		$adapter = $factory->getAdapter('memcache');
		$adapter->clear();

		return $adapter;
	}

	public function testIncrementNegativeValues() {
		$this->markTestSkipped('Memcache does not support incrementing negative values.');
	}

	public function testGetMemcache() {
		$adapter  = $this->getAdapter();
		$memcache = $adapter->getMemcache();

		$this->assertInstanceOf('Memcache', $memcache);
	}

	public function testGetMemcached() {
		$adapter   = $this->getAdapter();
		$memcached = $adapter->getMemcached();

		$this->assertInstanceOf('Memcache', $memcached);
	}

	public function testAddServerEx() {
		// at least call the method and see if it blows up
		$this->getAdapter()->addServerEx('127.0.0.2');
		$this->assertTrue(true);
	}

	public function testGetVersion() {
		$version = $this->getAdapter()->getMemcachedVersion();

		$this->assertInternalType('string', $version);
		$this->assertNotEmpty($version);
	}

	public function testGetStats() {
		$stats = $this->getAdapter()->getStats();

		$this->assertInternalType('array', $stats);
		$this->assertNotEmpty($stats);
	}
}
