<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

use wv\BabelCache\Adapter\MySQL;

class Adapter_MySQLTest extends Adapter_BaseTest {
	protected function getAdapter() {
		$factory = new TestFactory('fsadapter');

		if (!MySQL::isAvailable($factory)) {
			$this->markTestSkipped('MySQL is not available.');
		}

		$adapter = $factory->getAdapter('mysql');
		$adapter->clear();

		return $adapter;
	}
}
