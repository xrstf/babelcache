<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class TestFactory extends BabelCache_Factory {
	protected function getCacheDirectory() {
		$dir = dirname(__FILE__).'/fscache';
		if (!is_dir($dir)) mkdir($dir, 0777);

		return $dir;
	}
}
