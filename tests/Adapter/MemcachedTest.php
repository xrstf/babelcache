<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

use wv\BabelCache\Adapter\Memcached;

class Adapter_MemcachedTest extends Adapter_BaseTest {
	protected function getAdapter() {
		$factory = new TestFactory('fsadapter');

		if (!Memcached::isAvailable($factory)) {
			$this->markTestSkipped('Memcached is not available.');
		}

		$adapter = $factory->getAdapter('memcached');
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
}
