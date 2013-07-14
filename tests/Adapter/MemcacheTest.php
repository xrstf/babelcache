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

	public function testIncrement() {
		$adapter = $this->getAdapter();

		if (!($adapter instanceof wv\BabelCache\IncrementInterface)) {
			$this->markTestSkipped('Adapter does not implement IncrementInterface.');
		}

		$adapter->set('key', 41);

		$this->assertSame(41, $adapter->get('key'));
		$this->assertSame(42, $adapter->increment('key'));
		$this->assertSame(42, $adapter->get('key'));
		$this->assertSame(43, $adapter->increment('key'));
		$this->assertSame(43, $adapter->get('key'));

		// test a larger value
		$adapter->set('key', 80000);
		$this->assertSame(80001, $adapter->increment('key'));

		// test a negative value [disabled as memcached seems to have issues with negative values]
//		$adapter->set('key', -23);
//		$this->assertSame(-22, $adapter->increment('key'));

		// non-existing keys should not be created
		$this->assertFalse($adapter->increment('foo'));
		$this->assertFalse($adapter->exists('foo'));
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
