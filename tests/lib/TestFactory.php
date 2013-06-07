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

	public $sqliteConnection   = true;
	public $sqliteTableName    = 'tmp';
	public $mysqlConnection    = true;
	public $mysqlTableName     = 'tmp';
	public $redisAddresses     = array('host' => '127.0.0.1', 'port' => 6379);
	public $memcachedAddresses = array(array('127.0.0.1', 11211, 1));

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

	public function getMemcachedAddresses() {
		return $this->memcachedAddresses;
	}

	public function getSQLiteConnection() {
		if ($this->sqliteConnection === true) {
			$connection = SQLite::connect(':memory:');
			$connection->exec('DROP TABLE IF EXISTS "tmp"');
			$connection->exec('CREATE TABLE "tmp" ("keyhash" VARCHAR(50), "payload" BLOB, PRIMARY KEY ("keyhash"))');

			return $connection;
		}

		return $this->sqliteConnection;
	}

	public function getSQLiteTableName() {
		return $this->sqliteTableName;
	}

	public function getMySQLConnection() {
		static $called = 0;

		if ($this->mysqlConnection === true) {
			$host       = ++$called % 2 ? 'localhost' : 'localhost:3306';
			$connection = MySQL::connect($host, 'develop', 'develop', 'test');

			$connection->exec('DROP TABLE IF EXISTS tmp');
			$connection->exec('CREATE TABLE tmp (keyhash VARCHAR(50), payload BLOB, PRIMARY KEY (keyhash))');

			return $connection;
		}

		return $this->mysqlConnection;
	}

	public function getMySQLTableName() {
		return $this->mysqlTableName;
	}

	public function getRedisAddresses() {
		return $this->redisAddresses;
	}
}
