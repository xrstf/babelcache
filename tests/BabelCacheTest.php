<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class BabelCacheTest extends BabelCache {
	public function testString($str) {
		try {
			$this->checkString($str);
			return true;
		}
		catch (Exception $e) {
			return false;
		}
	}
}
