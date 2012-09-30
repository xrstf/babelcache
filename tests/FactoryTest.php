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
	 * @expectedException BabelCache_Exception
	 */
	public function testBuildCache() {
		$factory = new TestFactory();
		$obj     = $factory->getCache('BabelCache_Blackhole');

		$this->assertInstanceOf('BabelCache_Blackhole', $obj, 'The factory creates the right objects.');

		// should #fail
		$obj = $factory->getCache('UNKNOWN_CLASS');
	}
}
