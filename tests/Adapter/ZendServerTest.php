<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

use wv\BabelCache\Adapter\ZendServer;

class Adapter_ZendServerTest extends Adapter_BaseTest {
	protected function getAdapter() {
		$factory = new TestFactory('fsadapter');

		if (!ZendServer::isAvailable($factory)) {
			$this->markTestSkipped('ZendServer is not available.');
		}

		$adapter = new ZendServer();
		$adapter->clear();

		return $adapter;
	}
}
