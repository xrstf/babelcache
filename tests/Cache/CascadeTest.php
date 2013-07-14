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
use wv\BabelCache\Cache\Cascade;

class Cache_CascadeTest extends PHPUnit_Framework_TestCase {
	protected $primary;
	protected $secondary;

	protected function getCache() {
		$this->primary   = new Memory();
		$this->secondary = new Memory();

		return new Cascade($this->primary, $this->secondary);
	}

	public function testIsAvailable() {
		$this->assertTrue(Cascade::isAvailable());
	}

	public function testGetNonexisting() {
		$this->assertSame(12, $this->getCache()->get('t.foo', 'key', 12));
	}

	public function testGetKeyInPrimary() {
		$cache = $this->getCache();

		$this->primary->set('t', 'foo', 'valueX');

		$this->assertSame('valueX', $cache->get('t', 'foo'));
	}

	public function testGetKeyInSecondary() {
		$cache = $this->getCache();

		$this->secondary->set('t', 'foo', 'valueX');

		$this->assertSame('valueX', $cache->get('t', 'foo'));

		// now the value should also be available in the primary cache
		$this->assertSame('valueX', $this->primary->get('t', 'foo'));
	}

	public function testIfPrimaryHasPrecedence() {
		$cache = $this->getCache();

		$this->primary->set('t', 'foo', 'primary');
		$this->secondary->set('t', 'foo', 'secondary');

		$this->assertSame('primary', $cache->get('t', 'foo'));
	}

	public function testSetShouldStoreInBoth() {
		$cache = $this->getCache();

		$this->assertSame('myvalue', $cache->set('t', 'foo', 'myvalue'));

		$this->assertSame('myvalue', $this->primary->get('t', 'foo'));
		$this->assertSame('myvalue', $this->secondary->get('t', 'foo'));
	}

	public function testDeleteShouldWorkOnBoth() {
		$cache = $this->getCache();

		$this->primary->set('t', 'foo', 'primary');
		$this->secondary->set('t', 'foo', 'secondary');

		$cache->delete('t', 'foo');

		$this->assertFalse($this->primary->exists('t', 'foo'));
		$this->assertFalse($this->secondary->exists('t', 'foo'));
	}

	public function testExistsNeedsOnlyOne() {
		$cache = $this->getCache();

		$this->primary->set('t', 'foo', 'primary');
		$this->secondary->set('t', 'bar', 'secondary');

		$this->assertTrue($cache->exists('t', 'foo'));
		$this->assertTrue($cache->exists('t', 'bar'));
		$this->assertFalse($cache->exists('t', 'qux'));
	}

	public function testClearShouldWorkOnBoth() {
		$cache = $this->getCache();

		$this->primary->set('t', 'foo', 'primary');
		$this->secondary->set('t', 'foo', 'secondary');

		$cache->clear('t');

		$this->assertFalse($this->primary->exists('t', 'foo'));
		$this->assertFalse($this->secondary->exists('t', 'foo'));
	}

	public function testSettingPrefixShouldAffectBoth() {
		$cache = $this->getCache();

		$this->primary->set('t', 'foo', 'primary');
		$this->secondary->set('t', 'foo', 'secondary');

		$cache->setPrefix('foobar');

		$this->assertFalse($this->primary->exists('t', 'foo'));
		$this->assertFalse($this->secondary->exists('t', 'foo'));
	}

	public function testLockingShouldOnlyHappenInPrimaryCache() {
		$cache = $this->getCache();

		$this->assertTrue($cache->lock('t', 'foo'));

		$this->assertTrue($cache->hasLock('t', 'foo'));
		$this->assertTrue($this->primary->hasLock('t', 'foo'));
		$this->assertFalse($this->secondary->hasLock('t', 'foo'));

		$this->assertFalse($cache->lock('t', 'foo'));
		$this->assertTrue($cache->unlock('t', 'foo'));

		$this->assertFalse($cache->hasLock('t', 'foo'));
		$this->assertFalse($this->primary->hasLock('t', 'foo'));
		$this->assertFalse($this->secondary->hasLock('t', 'foo'));

		$this->assertFalse($cache->unlock('t', 'foo'));
		$this->assertTrue($cache->lock('t', 'foo'));
	}
}
