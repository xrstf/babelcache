<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

use wv\BabelCache\Cache\Blackhole;

class Cache_BlackholeTest extends PHPUnit_Framework_TestCase {
	public function testGet() {
		$cache = new Blackhole();
		$found   = null;
		$val     = $cache->get('ns', 'foo', null, $found);

		$this->assertFalse($found);
		$this->assertNull($val);
	}

	public function testSet() {
		$cache = new Blackhole();

		$this->assertSame('bar', $cache->set('ns', 'foo', 'bar'));

		$found = null;
		$val   = $cache->get('ns', 'foo', null, $found);

		$this->assertFalse($found);
		$this->assertNull($val);
	}

	public function testExists() {
		$cache = new Blackhole();
		$cache->set('ns', 'foo', 'content');

		$this->assertFalse($cache->exists('ns', 'foo'));
		$this->assertFalse($cache->exists('ns', 'bar'));
	}

	public function testDelete() {
		$cache = new Blackhole();
		$cache->set('ns', 'foo', 'content');

		$this->assertFalse($cache->exists('ns', 'foo'));
		$this->assertTrue($cache->delete('ns', 'foo'));
		$this->assertFalse($cache->exists('ns', 'foo'));
	}

	public function testSetPrefix() {
		$cache = new Blackhole();
		$this->assertNull($cache->setPrefix('foo')); // we must assert something, so assert that there is not retval
	}

	public function testLocking() {
		$cache = new Blackhole();

		$this->assertTrue($cache->lock('ns', 'foo'));
		$this->assertFalse($cache->hasLock('ns', 'foo'));
		$this->assertTrue($cache->lock('ns', 'foo'));
		$this->assertTrue($cache->unlock('ns', 'foo'));
		$this->assertFalse($cache->hasLock('ns', 'foo'));
		$this->assertTrue($cache->unlock('ns', 'foo'));
		$this->assertTrue($cache->lock('ns', 'foo'));
		$this->assertFalse($cache->hasLock('ns', 'foo'));
	}

	public function testClear() {
		$cache = new Blackhole();
		$this->assertTrue($cache->clear('ns'));
	}

	public function testIsAvailable() {
		$this->assertTrue(Blackhole::isAvailable());
	}
}
