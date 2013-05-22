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
		$factory = new TestFactory();

		// disable the blackhole overwrite
		$factory->setOverwrite('blackhole', null);

		$cache = $factory->getCache($cacheName, $forceGeneric);
		$this->assertInstanceOf($expectedClass, $cache);
	}

	public function cacheNameProvider() {
		return array(
			array('blackhole', false, 'wv\BabelCache\Cache\Generic'),
			array('memory',    false, 'wv\BabelCache\Cache\Memory'),
			array('memory',    true,  'wv\BabelCache\Cache\Generic')
		);
	}

	/**
	 * @expectedException  wv\BabelCache\Exception
	 */
	public function testBuildNonexistingCache() {
		$factory = new TestFactory();
		$factory->getCache('nonexisting');
	}

	/**
	 * @expectedException  wv\BabelCache\Exception
	 */
	public function testBuildNotAvailableCache() {
		$factory = new TestFactory();

		$factory->setAdapter('nope', 'NotAvailableAdapter');
		$factory->getCache('nope');
	}

	public function testGetAdapter() {
		$factory = new TestFactory();
		$this->assertInstanceOf('wv\BabelCache\Adapter\Memory', $factory->getAdapter('memory'));
	}
}
