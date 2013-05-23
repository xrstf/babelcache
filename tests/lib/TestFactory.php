<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

use wv\BabelCache\Factory;
use wv\BabelCache\Adapter\SQLite;

class TestFactory extends Factory {
	public function __construct() {
		parent::__construct();
		$this->setAdapter('blackhole', null);
	}

	public function getCacheDirectory() {
		$dir = __DIR__.'/../fscache';
		if (!is_dir($dir)) mkdir($dir, 0777);

		return $dir;
	}

	public function getSQLiteConnection() {
		return SQLite::connect(__DIR__.'/../test.sqlite');
	}
}
