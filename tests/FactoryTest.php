<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class FactoryTest extends PHPUnit_Framework_TestCase {
	/**
	 * @dataProvider  cacheNameProvider
	 */
	public function testBuildCache($cacheName, $forceGeneric, $expectedClass) {
		$factory = new TestFactory('fscache');

		// disable the filesystem overwrite
		$factory->setOverwrite('filesystem', null);

		$cache = $factory->getCache($cacheName, $forceGeneric);
		$this->assertInstanceOf($expectedClass, $cache);
	}

	public function cacheNameProvider() {
		return array(
			array('filesystem', false, 'wv\BabelCache\Cache\Generic'),
			array('memory',     false, 'wv\BabelCache\Cache\Memory'),
			array('memory',     true,  'wv\BabelCache\Cache\Generic')
		);
	}

	/**
	 * @expectedException  wv\BabelCache\Exception
	 */
	public function testBuildNonexistingCache() {
		$factory = new TestFactory('fscache');
		$factory->getCache('nonexisting');
	}

	/**
	 * @expectedException  wv\BabelCache\Exception
	 */
	public function testBuildNotAvailableCache() {
		$factory = new TestFactory('fscache');

		$factory->setAdapter('nope', 'NotAvailableAdapter');
		$factory->getCache('nope');
	}

	/**
	 * @expectedException  wv\BabelCache\Exception
	 */
	public function testTestNotExistingCache() {
		$factory = new TestFactory('fscache');
		$factory->isAvailable('nope');
	}

	public function testGetAdapter() {
		$factory = new TestFactory('fscache');
		$this->assertInstanceOf('wv\BabelCache\Adapter\Memory', $factory->getAdapter('memory'));
	}

	public function testGetAdapters() {
		$factory  = new TestFactory('fscache');
		$adapters = $factory->getAdapters(false);

		$this->assertInternalType('array', $adapters);
		$this->assertNotEmpty($adapters);

		$adapters = $factory->getAdapters(true);

		$this->assertInternalType('array', $adapters);
		$this->assertNotEmpty($adapters);
		$this->assertSame(0, key($adapters));
	}

	public function testGetAvailableAdapters() {
		$factory  = new TestFactory('fscache');
		$adapters = $factory->getAvailableAdapters(false);

		$this->assertInternalType('array', $adapters);
		$this->assertNotEmpty($adapters);

		$adapters = $factory->getAvailableAdapters(true);

		$this->assertInternalType('array', $adapters);
		$this->assertNotEmpty($adapters);
		$this->assertSame(0, key($adapters));
	}

	public function testSetOverwrite() {
		$factory = new TestFactory('fscache');
		$factory->setOverwrite('memory', 'DummyCache');

		// overwrites should not affect direct access to adapters
		$adapter = $factory->getAdapter('memory');
		$this->assertInstanceOf('wv\BabelCache\Adapter\Memory', $adapter);

		// but they should affect the cache choice
		$cache = $factory->getCache('memory');
		$this->assertInstanceOf('DummyCache', $cache);
	}

	public function testMemcachedIsDisabledByDefault() {
		$factory = new TestFactory('fscache');

		// make sure the default implementation is called
		$method  = $this->getMethod('wv\BabelCache\Factory', 'getMemcachedAddresses');
		$result  = $method->invokeArgs($factory, array());

		$this->assertNull($result);
	}

	public function testRedisIsDisabledByDefault() {
		$factory = new TestFactory('fscache');

		// make sure the default implementation is called
		$method  = $this->getMethod('wv\BabelCache\Factory', 'getRedisAddresses');
		$result  = $method->invokeArgs($factory, array());

		$this->assertNull($result);
	}

	/**
	 * @expectedException  wv\BabelCache\Exception
	 * @dataProvider       constructCallProvider
	 */
	public function testCatchBadConfig($methodName) {
		$factory = new TestFactory('fsadapter');
		$factory->memcachedAddresses = null;
		$factory->redisAddresses     = null;

		$method = $this->getMethod('TestFactory', $methodName);
		$method->invokeArgs($factory, array('DummyClassName'));
	}

	public function constructCallProvider() {
		return array(
			array('constructRedis'),
			array('constructMemcache'),
			array('constructMemcached'),
			array('constructMemcachedSASL')
		);
	}

	protected function getMethod($class, $name) {
		$class  = new ReflectionClass(is_string($class) ? $class : get_class($class));
		$method = $class->getMethod($name);

		$method->setAccessible(true);

		return $method;
	}
}
