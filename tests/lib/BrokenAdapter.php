<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

use wv\BabelCache\Adapter\Memory;

class BrokenAdapter extends Memory {
	public function set($key, $value) {
		return false;
	}

	public function clear() {
		return false;
	}

	public function increment($key) {
		return false;
	}
}
