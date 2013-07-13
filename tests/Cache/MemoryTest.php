<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

use wv\BabelCache\Cache\Memory;

class Cache_MemoryTest extends Cache_BaseTest {
	protected function getCache() {
		return new Memory();
	}

	public function testIsAvailable() {
		$this->assertTrue(Memory::isAvailable());
	}
}
