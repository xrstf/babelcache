<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

use wv\BabelCache\Adapter\Filesystem;

class Adapter_FilesystemTest extends Adapter_BaseTest {
	protected function getAdapter() {
		$factory = new TestFactory('fsadapter');

		if (!Filesystem::isAvailable($factory)) {
			$this->markTestSkipped('Filesystem is not available.');
		}

		$adapter = $factory->getAdapter('filesystem');
		$adapter->clear();

		return $adapter;
	}

	/**
	 * @expectedException wv\BabelCache\Exception
	 */
	public function testBadDirectory() {
		new Filesystem('mumblefoo');
	}
}
