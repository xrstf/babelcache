<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

use wv\BabelCache\Adapter\Redis;

class Adapter_RedisTest extends Adapter_BaseTest {
	protected function getAdapter() {
		$factory = new TestFactory('fsadapter');

		if (!Redis::isAvailable($factory)) {
			$this->markTestSkipped('Redis is not available.');
		}

		$adapter = $factory->getAdapter('redis');
		$adapter->clear();

		return $adapter;
	}

	public function testGetClient() {
		$adapter = $this->getAdapter();
		$client  = $adapter->getClient();

		$this->assertInstanceOf('Predis\Client', $client);
	}
}
