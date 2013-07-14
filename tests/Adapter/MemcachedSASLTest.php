<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

use wv\BabelCache\Adapter\MemcachedSASL;

class Adapter_MemcachedSASLTest extends Adapter_BaseTest {
	protected function getAdapter() {
		$factory = new TestFactory('fsadapter');

		if (!MemcachedSASL::isAvailable($factory)) {
			$this->markTestSkipped('Memcached SASL is not available.');
		}

		$adapter = $factory->getAdapter('memcachedsasl');
		$adapter->clear();

		return $adapter;
	}

	public function testIncrementNegativeValues() {
		$this->markTestSkipped('Memcached does not support incrementing negative values.');
	}
}
