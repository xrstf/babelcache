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
use wv\BabelCache\Adapter\SQLite as SQLiteAdapter;
use wv\BabelCache\Cache\SQLite as SQLiteCache;
use wv\BabelCache\Adapter\MySQL as MySQLAdapter;
use wv\BabelCache\Cache\MySQL as MySQLCache;

class TestFactory extends Factory {
	protected $cacheDir;

	public $sqliteConnection    = true;
	public $sqliteTableName     = 'tmp_adapter';
	public $mysqlConnection     = true;
	public $mysqlTableName      = 'tmp_adapter';
	public $redisAddresses      = array('host' => '127.0.0.1', 'port' => 6379);
	public $memcachedAddresses  = array(array('127.0.0.1', 11211, 1));
	public $elastiCacheEndpoint = array('127.0.0.1', 11211);

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
			if ($this->sqliteTableName === 'tmp_adapter') {
				$connection = SQLiteAdapter::connect(':memory:');
			}
			else {
				$connection = SQLiteCache::connect(':memory:');
			}

			$connection->exec('DROP TABLE IF EXISTS "tmp_adapter"');
			$connection->exec('DROP TABLE IF EXISTS "tmp_cache"');
			$connection->exec('CREATE TABLE "tmp_adapter" ("keyhash" VARCHAR(50), "payload" BLOB, PRIMARY KEY ("keyhash"))');
			$connection->exec('CREATE TABLE "tmp_cache" ("prefix" VARCHAR(50), "namespace" VARCHAR(255), "keyname" VARCHAR(255), "payload" BLOB, PRIMARY KEY ("prefix", "namespace", "keyname"))');

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
			$host   = ++$called % 2 ? 'localhost' : 'localhost:3306';
			$travis = getenv('TRAVIS') !== false;
			$user   = $travis ? 'travis' : 'develop';
			$pass   = $travis ? '' : 'develop';

			if ($this->mysqlTableName === 'tmp_adapter') {
				$connection = MySQLAdapter::connect($host, $user, $pass, 'test');
			}
			else {
				$connection = MySQLCache::connect($host, $user, $pass, 'test');
			}

			$connection->exec('DROP TABLE IF EXISTS tmp_adapter');
			$connection->exec('DROP TABLE IF EXISTS tmp_cache');

			$connection->exec('CREATE TABLE tmp_adapter (keyhash VARCHAR(50), payload BLOB, PRIMARY KEY (keyhash))');
			$connection->exec('CREATE TABLE tmp_cache (prefix VARBINARY(50), namespace VARBINARY(255), keyname VARBINARY(255), payload BLOB, PRIMARY KEY (prefix, namespace, keyname))');

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

	public function getElastiCacheEndpoint() {
		return $this->elastiCacheEndpoint;
	}
}
