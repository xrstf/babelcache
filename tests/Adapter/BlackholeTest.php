<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

use wv\BabelCache\Adapter\Blackhole;

class Adapter_BlackholeTest extends PHPUnit_Framework_TestCase {
	public function testGet() {
		$adapter = new Blackhole();
		$found   = null;
		$val     = $adapter->get('foo', $found);

		$this->assertFalse($found);
		$this->assertNull($val);
	}

	public function testSet() {
		$adapter = new Blackhole();

		$this->assertTrue($adapter->set('foo', 'bar'));

		$found = null;
		$val   = $adapter->get('foo', $found);

		$this->assertFalse($found);
		$this->assertNull($val);
	}

	public function testExists() {
		$adapter = new Blackhole();
		$adapter->set('foo', 'content');

		$this->assertFalse($adapter->exists('foo'));
		$this->assertFalse($adapter->exists('bar'));
	}

	public function testDelete() {
		$adapter = new Blackhole();
		$adapter->set('foo', 'content');

		$this->assertFalse($adapter->exists('foo'));
		$this->assertTrue($adapter->delete('foo'));
		$this->assertFalse($adapter->exists('foo'));
	}

	public function testClear() {
		$adapter = new Blackhole();
		$this->assertTrue($adapter->clear());
	}

	public function testIsAvailable() {
		$this->assertTrue(Blackhole::isAvailable());
	}
}
