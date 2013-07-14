<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

use wv\BabelCache\Cache\Memory;
use wv\BabelCache\Decorator\Expiring;

class Decorator_ExpiringTest extends PHPUnit_Framework_TestCase {
	protected function getCache($ttl) {
		return new Expiring(new Memory(), $ttl);
	}

	public function testIsAvailable() {
		$this->assertTrue(Expiring::isAvailable());
	}

	public function testGetNonexisting() {
		$this->assertSame('default', $this->getCache(5)->get('t', 'foo', 'default'));
	}

	public function testSet() {
		$cache = $this->getCache(5);

		$cache->set('t', 'foo', 'bar');
		$this->assertSame('bar', $cache->get('t', 'foo'));
	}

	public function testItemExpires() {
		$cache = $this->getCache(2);

		$cache->set('t', 'foo', 'bar');
		sleep(3);

		$this->assertFalse($cache->exists('t', 'foo'));
		$this->assertNull($cache->get('t', 'foo'));
	}

	public function testCustomTtlOverwritesDefaultOne() {
		$cache = $this->getCache(2);

		$cache->set('t', 'foo', 'bar', 5);
		sleep(3);

		$this->assertTrue($cache->exists('t', 'foo'));
		sleep(3);

		$this->assertFalse($cache->exists('t', 'foo'));
	}

	public function testTtlIsAlwaysRelative() {
		$cache = $this->getCache(2);

		$cache->set('t', 'foo', 'bar');
		sleep(4);

		$this->assertFalse($cache->exists('t', 'foo'));
		$this->assertNull($cache->get('t', 'foo'));

		// If the current time would have been just fetched once and then remembered
		// forever, this new write would expire immediately (it would just live for
		// 2 seconds, beginning before we slept for 4 seconds).
		$cache->set('t', 'foo', 'qux');

		$this->assertTrue($cache->exists('t', 'foo'));
		$this->assertSame('qux', $cache->get('t', 'foo'));
	}

	public function testAllowImmediateExpire() {
		$cache = $this->getCache(2);

		$cache->set('t', 'foo', 'bar', -1);

		$this->assertFalse($cache->exists('t', 'foo'));
		$this->assertNull($cache->get('t', 'foo'));
	}
}
