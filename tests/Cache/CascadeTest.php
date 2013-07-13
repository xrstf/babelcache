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
}
