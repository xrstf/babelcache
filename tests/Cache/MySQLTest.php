<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

use wv\BabelCache\Cache\MySQL;

class Cache_MySQLTest extends Cache_BaseTest {
	protected function getCache() {
		$factory = new TestFactory('fscache');
		$factory->mysqlTableName = 'tmp_cache';

		if (!MySQL::isAvailable($factory)) {
			$this->markTestSkipped('MySQL is not available.');
		}

		return $factory->getCache('mysql');
	}
}
