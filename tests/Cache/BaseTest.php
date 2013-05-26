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
		$l1    = 't.a'.uniqid();
		$l2    = $l1.'.b'.uniqid();
		$l3    = $l2.'.c'.uniqid();

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
	public function testRemove() {
		$cache = $this->getCache();
		$cache->set('t.foo', 'key', 'abc');

		$this->assertTrue($cache->remove('t.foo', 'key'));
		$this->assertFalse($cache->remove('t.foo', 'key'));
		$this->assertFalse($cache->exists('t.foo', 'key'));
	}
}
