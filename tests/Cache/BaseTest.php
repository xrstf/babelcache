<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

abstract class Cache_BaseTest extends PHPUnit_Framework_TestCase {
	abstract protected function getCache();

	public function testGetNonexisting() {
		$cache = $this->getCache();

		$this->assertSame(12, $cache->get('t.foo', 'key', 12));
		$this->assertFalse($cache->get('t.foo', 'key', false));
		$this->assertNull($cache->get('t.foo', 'key', null));
	}

	public function testSetPrefix() {
		$cache = $this->getCache();

		$cache->setPrefix('foobar');
		$cache->set('t.foo', 'key', 'value');

		$cache->setPrefix('qux');
		$this->assertSame('default', $cache->get('t.foo', 'key', 'default'));
		$cache->set('t.foo', 'key', 'mumblefoo');
		$this->assertSame('mumblefoo', $cache->get('t.foo', 'key'));

		$cache->setPrefix('foobar');
		$this->assertSame('value', $cache->get('t.foo', 'key'));
	}

	/**
	 * @dataProvider setGetProvider
	 */
	public function testSetGet($namespace, $key, $value) {
		$cache  = $this->getCache();
		$result = $cache->set($namespace, $key, $value);
		$this->assertSame($value, $result, 'set should return the set value');

		$read = $cache->get($namespace, $key, null, $found);

		// test if found was set
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
			array('t.foo.bar', 'blub', 1),
			array('t.foo.bar', 'blub', 3.41),
			array('t.foo.bar', 'blub', false),
			array('t.foo.bar', 'blub', true),
			array('t.foo.bar', 'blub', null),
			array('t.foo.bar', 'blub', ''),
			array('t.foo.bar', 'blub', 'i am a string!'),
			array('t.foo.bar', 'blub', array()),
			array('t.foo.bar', 'blub', array(null)),
			array('t.foo.bar', 'blub', array(1)),
			array('t.foo.bar', 'blub', new stdClass()),
			array('t.foo.bar', 'blub', $foo)
		);
	}

	/**
	 * @dataProvider clearProvider
	 * @depends      testSetGet
	 */
	public function testClear($clearLevel, $exists1, $exists2, $exists3) {
		$cache = $this->getCache();
		$l1    = 't.AA_'.mt_rand().'_AA';
		$l2    = $l1.'.BB_'.mt_rand().'_BB';
		$l3    = $l2.'.CC_'.mt_rand().'_CC';

		$cache->set($l1, 'foo', 'bar');
		$cache->set($l2, 'foo', 'bar');
		$cache->set($l3, 'foo', null);

		$this->assertTrue($cache->clear($$clearLevel, true));
		$this->assertSame($exists1, $cache->exists($l1, 'foo'));
		$this->assertSame($exists2, $cache->exists($l2, 'foo'));
		$this->assertSame($exists3, $cache->exists($l3, 'foo'));
	}

	public function clearProvider() {
		return array(
			array('l3', true,  true,  false),
			array('l2', true,  false, false),
			array('l1', false, false, false)
		);
	}

	/**
	 * @depends  testSetGet
	 */
	public function testNonRecursiveClear() {
		$cache = $this->getCache();

		$cache->set('t',         'key', 'value');
		$cache->set('t.foo',     'key', 'value');
		$cache->set('t.foo.foo', 'key', 'value');

		$this->assertTrue($cache->clear('t.foo', false));
		$this->assertTrue($cache->exists('t', 'key'));
		$this->assertFalse($cache->exists('t.foo', 'key'));

		// This can be true or false. Both are perfectly fine.
		$this->assertInternalType('boolean', $cache->exists('t.foo.foo', 'key'));
	}

	/**
	 * @depends            testClear
	 * @expectedException  wv\BabelCache\Exception
	 * @dataProvider       emptyValuesProvider
	 */
	public function testClearShouldRequireANamespace($namespace) {
		$this->getCache()->clear($namespace);
	}

	public function emptyValuesProvider() {
		return array(
			array(null),
			array(false),
			array('')
		);
	}

	/**
	 * @depends  testSetGet
	 */
	public function testOverwritingValues() {
		$cache = $this->getCache();

		$cache->set('t.foo', 'key', 'abc');
		$cache->set('t.foo', 'key', 'xyz');

		$this->assertSame('xyz', $cache->get('t.foo', 'key'));
	}

	/**
	 * @depends  testSetGet
	 */
	public function testExists() {
		$cache = $this->getCache();

		$cache->set('t.foo', 'key', 'abc');
		$this->assertTrue($cache->exists('t.foo', 'key'));
		$this->assertFalse($cache->exists('t.foo', 'KEY'));
		$this->assertFalse($cache->exists('t.FOO', 'KEY'));
		$this->assertFalse($cache->exists('t', 'foo'));
	}

	/**
	 * @depends  testExists
	 */
	public function testDelete() {
		$cache = $this->getCache();
		$cache->set('t.foo', 'key', 'abc');

		$this->assertTrue($cache->delete('t.foo', 'key'));
		$this->assertFalse($cache->delete('t.foo', 'key'));
		$this->assertFalse($cache->exists('t.foo', 'key'));
	}

	/**
	 * @depends  testSetGet
	 */
	public function testNamespaceAndKeyAreCaseSensitive() {
		$cache = $this->getCache();

		$cache->set('t.foo', 'key', 'fk content');
		$cache->set('t.FOO', 'key', 'Fk content');

		$this->assertTrue($cache->exists('t.foo', 'key'));
		$this->assertTrue($cache->exists('t.FOO', 'key'));
		$this->assertFalse($cache->exists('t.Foo', 'key'));
		$this->assertFalse($cache->exists('t.foo', 'KEY'));

		$cache->set('t.foo', 'KEY', 'fK content');

		$this->assertTrue($cache->exists('t.foo', 'key'));
		$this->assertTrue($cache->exists('t.FOO', 'key'));
		$this->assertFalse($cache->exists('t.Foo', 'key'));
		$this->assertTrue($cache->exists('t.foo', 'KEY'));

		$this->assertSame('fk content', $cache->get('t.foo', 'key'));
		$this->assertSame('Fk content', $cache->get('t.FOO', 'key'));
		$this->assertSame('fK content', $cache->get('t.foo', 'KEY'));

		$cache->delete('t.foo', 'key');

		$this->assertFalse($cache->exists('t.foo', 'key'));
		$this->assertTrue($cache->exists('t.FOO', 'key'));
		$this->assertTrue($cache->exists('t.foo', 'KEY'));
	}

	public function testLocking() {
		$cache = $this->getCache();

		// there should be no lock already
		$this->assertFalse($cache->hasLock('t.locks', 'foo'));
		$this->assertFalse($cache->hasLock('t.locks', 'bar'));

		// we should only be able to lock once
		$this->assertTrue($cache->lock('t.locks', 'foo'));
		$this->assertFalse($cache->lock('t.locks', 'foo'));

		// but locking other keys should still work
		$this->assertTrue($cache->lock('t.locks', 'bar'));

		// now we have locks
		$this->assertTrue($cache->hasLock('t.locks', 'foo'));
		$this->assertTrue($cache->hasLock('t.locks', 'bar'));

		// a clear should delete the locks as well
		$cache->clear('x');

		// locks are gone
		$this->assertFalse($cache->hasLock('t.locks', 'foo'));
		$this->assertFalse($cache->hasLock('t.locks', 'bar'));

		// ... so we can lock again
		$this->assertTrue($cache->lock('t.locks', 'foo'));
		$this->assertTrue($cache->hasLock('t.locks', 'foo'));

		// unlocking should have the same effect
		$this->assertTrue($cache->unlock('t.locks', 'foo'));
		$this->assertFalse($cache->hasLock('t.locks', 'foo'));
		$this->assertTrue($cache->lock('t.locks', 'foo'));

		// unlocking a non-existing lock for a key should not work
		$this->assertFalse($cache->unlock('t.locks', 'bar'));
	}
}
