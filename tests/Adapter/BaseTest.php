<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

abstract class Adapter_BaseTest extends PHPUnit_Framework_TestCase {
	abstract protected function getAdapter();

	/**
	 * @dataProvider setGetProvider
	 */
	public function testSetGet($key, $value) {
		$adapter = $this->getAdapter();

		// correct return value?
		$result = $adapter->set($key, $value);
		$this->assertTrue($result, 'set should return true or false');

		// test if $found was set
		$read = $adapter->get($key, $found);
		$this->assertTrue($found, 'value was not found, but it should be there.');

		if (is_object($value)) {
			// we do not require the same identity
			$this->assertEquals($value, $read, 'value should be an equal object');
		}
		else {
			$this->assertSame($value, $read, 'value should be available immediately');
		}
	}

	public function setGetProvider() {
		$foo = new stdClass();
		$foo->x = 1;

		return array(
			array('blub', 1),
			array('blub', 3.41),
			array('blub', false),
			array('blub', true),
			array('blub', null),
			array('blub', ''),
			array('blub', 'i am a string!'),
			array('blub', array()),
			array('blub', array(null)),
			array('blub', array(1)),
			array('blub', new stdClass()),
			array('blub', $foo)
		);
	}

	/**
	 * @depends  testSetGet
	 */
	public function testGetNonexisting() {
		$adapter = $this->getAdapter();
		$found   = null;
		$result  = $adapter->get('foo', $found);

		$this->assertNull($result);
		$this->assertFalse($found);
	}

	/**
	 * @depends  testSetGet
	 */
	public function testClear() {
		$adapter = $this->getAdapter();

		$adapter->set('foo', 'bar');
		$adapter->set('bar', 'bar');

		$this->assertTrue($adapter->clear());
		$this->assertFalse($adapter->exists('foo'));
		$this->assertFalse($adapter->exists('bar'));
	}

	/**
	 * @depends  testSetGet
	 */
	public function testOverwritingValues() {
		$adapter = $this->getAdapter();

		$adapter->set('key', 'abc');
		$adapter->set('key', 'xyz');

		$this->assertSame('xyz', $adapter->get('key'));
	}

	/**
	 * @depends  testSetGet
	 */
	public function testExists() {
		$adapter = $this->getAdapter();

		$adapter->set('key', 'abc');

		$this->assertTrue($adapter->exists('key'));
		$this->assertFalse($adapter->exists('KEY'));
		$this->assertFalse($adapter->exists('foo'));
	}

	/**
	 * @depends  testExists
	 */
	public function testDelete() {
		$adapter = $this->getAdapter();
		$adapter->set('key', 'abc');

		$this->assertTrue($adapter->delete('key'));
		$this->assertFalse($adapter->exists('key'));
		$this->assertFalse($adapter->delete('key'));
	}

	/**
	 * @depends  testSetGet
	 */
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

		// non-existing keys should not be created
		$this->assertFalse($adapter->increment('foo'));
		$this->assertFalse($adapter->exists('foo'));
	}

	/**
	 * @depends  testSetGet
	 */
	public function testIncrementNegativeValues() {
		$adapter = $this->getAdapter();

		if (!($adapter instanceof wv\BabelCache\IncrementInterface)) {
			$this->markTestSkipped('Adapter does not implement IncrementInterface.');
		}

		$adapter->set('key', -23);
		$this->assertSame(-22, $adapter->increment('key'));
	}

	/**
	 * @depends  testSetGet
	 */
	public function testKeysAreCaseSensitive() {
		$adapter = $this->getAdapter();

		$adapter->set('foo', 'content');

		$this->assertTrue($adapter->exists('foo'));
		$this->assertFalse($adapter->exists('Foo'));
		$this->assertFalse($adapter->exists('FOO'));

		$adapter->set('FOO', 'FOO content');

		$this->assertTrue($adapter->exists('foo'));
		$this->assertFalse($adapter->exists('Foo'));
		$this->assertTrue($adapter->exists('FOO'));

		$this->assertSame('content',     $adapter->get('foo'));
		$this->assertSame('FOO content', $adapter->get('FOO'));

		$adapter->delete('foo');

		$this->assertFalse($adapter->exists('foo'));
		$this->assertTrue($adapter->exists('FOO'));
	}

	public function testLocking() {
		$adapter = $this->getAdapter();

		if (!($adapter instanceof wv\BabelCache\LockingInterface)) {
			$this->markTestSkipped('Adapter does not implement LockingInterface.');
		}

		// there should be no lock already
		$this->assertFalse($adapter->hasLock('foo'));
		$this->assertFalse($adapter->hasLock('bar'));

		// we should only be able to lock once
		$this->assertTrue($adapter->lock('foo'));
		$this->assertFalse($adapter->lock('foo'));

		// but locking other keys should still work
		$this->assertTrue($adapter->lock('bar'));

		// now we have locks
		$this->assertTrue($adapter->hasLock('foo'));
		$this->assertTrue($adapter->hasLock('bar'));

		// a clear should delete the locks as well
		$adapter->clear();

		// locks are gone
		$this->assertFalse($adapter->hasLock('foo'));
		$this->assertFalse($adapter->hasLock('bar'));

		// ... so we can lock again
		$this->assertTrue($adapter->lock('foo'));
		$this->assertTrue($adapter->hasLock('foo'));

		// unlocking should have the same effect
		$this->assertTrue($adapter->unlock('foo'));
		$this->assertFalse($adapter->hasLock('foo'));
		$this->assertTrue($adapter->lock('foo'));

		// unlocking a non-existing lock for a key should not work
		$this->assertFalse($adapter->unlock('bar'));
	}
}
