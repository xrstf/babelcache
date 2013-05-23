<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class Adapter_AdapterTest extends PHPUnit_Framework_TestCase {
	protected static $factory;
	protected static $available;

	public static function setUpBeforeClass() {
		self::$factory   = new TestFactory('fsadapter');
		self::$available = self::$factory->getAvailableAdapters();
	}

	protected function tearDown() {
		foreach (self::$available as $adapter => $className) {
			self::$factory->getAdapter($adapter)->clear();
		}
	}

	/**
	 * @dataProvider setGetProvider
	 */
	public function testSetGet($adapter, $key, $value) {
		$adapter = $this->getAdapter($adapter);

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

		return $this->buildDataSet(array(
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
		));
	}

	/**
	 * @dataProvider adapterProvider
	 * @depends      testSetGet
	 */
	public function testClear($adapter) {
		$adapter = $this->getAdapter($adapter);

		$adapter->set('foo', 'bar');
		$adapter->set('bar', 'bar');

		$this->assertTrue($adapter->clear());
		$this->assertFalse($adapter->exists('foo'));
		$this->assertFalse($adapter->exists('bar'));
	}

	/**
	 * @dataProvider adapterProvider
	 * @depends      testSetGet
	 */
	public function testOverwritingValues($adapter) {
		$adapter = $this->getAdapter($adapter);

		$adapter->set('key', 'abc');
		$adapter->set('key', 'xyz');

		$this->assertSame('xyz', $adapter->get('key'));
	}

	/**
	 * @dataProvider adapterProvider
	 * @depends      testSetGet
	 */
	public function testExists($adapter) {
		$adapter = $this->getAdapter($adapter);

		$adapter->set('key', 'abc');

		$this->assertTrue($adapter->exists('key'));
		$this->assertFalse($adapter->exists('KEY'));
		$this->assertFalse($adapter->exists('foo'));
	}

	/**
	 * @dataProvider adapterProvider
	 * @depends      testExists
	 */
	public function testRemove($adapter) {
		$adapter = $this->getAdapter($adapter);
		$adapter->set('key', 'abc');

		$this->assertTrue($adapter->remove('key'));
		$this->assertFalse($adapter->exists('key'));
		$this->assertFalse($adapter->remove('key'));
	}

	/**
	 * @dataProvider adapterProvider
	 * @depends      testSetGet
	 */
	public function testIncrement($adapterName) {
		$adapter = $this->getAdapter($adapterName);

		if (!($adapter instanceof wv\BabelCache\IncrementInterface)) {
			$this->markTestSkipped($adapterName.' does not implement IncrementInterface.');
		}

		$adapter->set('key', 41);

		$this->assertSame(41, $adapter->get('key'));
		$this->assertSame(42, $adapter->increment('key'));
		$this->assertSame(42, $adapter->get('key'));
		$this->assertSame(43, $adapter->increment('key'));
		$this->assertSame(43, $adapter->get('key'));

		// non-existing keys should not be created
		$this->assertFalse($adapter->increment('foo'));
		$this->assertFalse($adapter->exists('foo'));
	}

	public function adapterProvider() {
		return $this->buildDataSet(null);
	}

	protected function getAdapter($adapter, $mark = true) {
		if (!isset(self::$available[$adapter])) {
			if ($mark) $this->markTestSkipped($adapter.' is not available.');
			return null;
		}

		return self::$factory->getAdapter($adapter);
	}

	protected function buildDataSet($sets) {
		$result  = array();
		$factory = new TestFactory('fsadapter'); // setUpBeforeClass has not yet been called

		foreach ($factory->getAdapters(true) as $adapter) {
			if ($sets === null) {
				$result[] = array($adapter);
			}
			else {
				foreach ($sets as $set) {
					array_unshift($set, $adapter);
					$result[] = $set;
				}
			}
		}

		return $result;
	}
}
