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
use wv\BabelCache\Adapter\MySQL;

class TestFactory extends Factory {
	protected $cacheDir;

	public function __construct($cacheDir) {
		parent::__construct();

		$this->setAdapter('blackhole', null);
		$this->cacheDir = $cacheDir;
	}

	public function getCacheDirectory() {
		$dir = __DIR__.'/../'.$this->cacheDir;
		if (!is_dir($dir)) mkdir($dir, 0777);

		return $dir;
	}

	public function getSQLiteConnection() {
		$connection = SQLite::connect(':memory:');
		$connection->exec('DROP TABLE IF EXISTS "tmp"');
		$connection->exec('CREATE TABLE "tmp" ("keyhash" VARCHAR(50), "payload" BLOB, PRIMARY KEY ("keyhash"))');

		return $connection;
	}

	public function getSQLiteTableName() {
		return 'tmp';
	}

	public function getMySQLConnection() {
		$connection = MySQL::connect('localhost', 'develop', 'develop', 'test');
		$connection->exec('DROP TABLE IF EXISTS tmp');
		$connection->exec('CREATE TABLE tmp (keyhash VARCHAR(50), payload BLOB, PRIMARY KEY (keyhash))');

		return $connection;
	}

	public function getMySQLTableName() {
		return 'tmp';
	}
}
