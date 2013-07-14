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
use wv\BabelCache\Decorator\Base;

class Decorator_BaseTest extends Cache_BaseTest {
	protected function getCache() {
		return new Base(new Memory());
	}
}
