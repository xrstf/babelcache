<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

use wv\BabelCache\Cache\Filesystem;

class Cache_FilesystemTest extends Cache_BaseTest {
	protected function getCache() {
		$factory = new TestFactory('fscache');
		$cache   = $factory->getCache('filesystem');

		$cache->clear('t', true);

		return $cache;
	}

	public function testSetPrefix() {
		$cache = $this->getCache();

		$cache->setPrefix('foobar');
		$cache->set('t.foo', 'key', 'value');

		$cache->setPrefix('qux');
		$this->assertSame('default', $cache->get('t.foo', 'key', 'default'));
	}

	/**
	 * @expectedException  wv\BabelCache\Exception
	 */
	public function testBadDataDirectory() {
		new Filesystem('mumblefoo');
	}
}
