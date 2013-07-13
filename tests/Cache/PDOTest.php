<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

use wv\BabelCache\Cache\SQLite;

class Cache_PDOTest extends PHPUnit_Framework_TestCase {
	/**
	 * @expectedException  wv\BabelCache\Exception
	 */
	public function testGetBadStatement() {
		// build SQLite dummy cache
		$factory = new TestFactory('fsadapter');

		if (!SQLite::isAvailable($factory)) {
			$this->markTestSkipped('SQLite is not available.');
		}

		$cache = $factory->getCache('sqlite');

		// get access to the protected getStatement()
		$method = $this->getMethod($cache, 'getStatement');
		$result = $method->invokeArgs($cache, array('--test--'));
	}

	protected function getMethod($class, $name) {
		$class  = new ReflectionClass(is_string($class) ? $class : get_class($class));
		$method = $class->getMethod($name);

		$method->setAccessible(true);

		return $method;
	}
}
