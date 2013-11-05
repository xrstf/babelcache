<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace wv\BabelCache;

use PDO;

/**
 * Simple Factory
 *
 * Use this if you don't need fancy stuff and just want BabelCache to run.
 *
 * @package BabelCache
 */
class SimpleFactory extends Factory {
	protected $memcachedAddr;
	protected $memcachedAuth;
	protected $redisAddr;
	protected $elastiCacheEndpoint;
	protected $cachePrefix;
	protected $cacheDir;
	protected $sqliteConn;
	protected $sqliteTable;
	protected $mysqlConn;
	protected $mysqlTable;

	/**
	 * Set memcached server addresses
	 *
	 * @param array $addresses  array(array(host, port, weight))
	 */
	public function setMemcachedAddresses(array $addresses = null) {
		$this->memcachedAddr = $addresses;
	}

	/**
	 * Set memcached SASL auth data
	 *
	 * @param mixed $auth  array(username, password) or null to disable SASL support
	 */
	public function setMemcachedAuthentication(array $auth = null) {
		$this->memcachedAuth = $auth;
	}

	/**
	 * Set Redis server addresses
	 *
	 * @param array $addresses  [{host: ..., port: ...}]
	 */
	public function setRedisAddresses(array $addresses = null) {
		$this->redisAddr = $addresses;
	}

	/**
	 * Set ElastiCache configuration endpoint
	 *
	 * @param array $endpoint  [host, port]
	 */
	public function setElastiCacheEndpoint(array $endpoint = null) {
		$this->elastiCacheEndpoint = $endpoint;
	}

	/**
	 * Set caching prefix
	 *
	 * @param string $prefix  the prefix
	 */
	public function setCachePrefix($prefix) {
		$this->cachePrefix = $prefix;
	}

	/**
	 * Set cache directory
	 *
	 * @param string $dir  full directory path
	 */
	public function setCacheDirectory($dir) {
		$this->cacheDir = $dir;
	}

	/**
	 * Set SQLite connection data
	 *
	 * @param PDO    $connection
	 * @param string $tableName
	 */
	public function setSQLite(PDO $connection, $tableName) {
		$this->sqliteConn  = $connection;
		$this->sqliteTable = $tableName;
	}

	/**
	 * Set MySQL connection data
	 *
	 * @param PDO    $connection
	 * @param string $tableName
	 */
	public function setMySQL(PDO $connection, $tableName) {
		$this->mysqlConn  = $connection;
		$this->mysqlTable = $tableName;
	}

	/**
	 * Return memcached server addresses
	 *
	 * @return array  array(array(host, port, weight))
	 */
	public function getMemcachedAddresses() {
		return $this->memcachedAddr;
	}

	/**
	 * Return memcached SASL auth data
	 *
	 * @return mixed  array(username, password) or null to disable SASL support
	 */
	public function getMemcachedAuthentication() {
		return $this->memcachedAuth;
	}

	/**
	 * Return Redis server addresses
	 *
	 * @return array  [{host: ..., port: ...}] or null
	 */
	public function getRedisAddresses() {
		return $this->redisAddr;
	}

	/**
	 * Return ElastiCache configuration endpoint
	 *
	 * This method should return a tupel of [hostname, port].
	 *
	 * @return array  array(host, port)
	 */
	public function getElastiCacheEndpoint() {
		return $this->elastiCacheEndpoint;
	}

	/**
	 * Return caching prefix
	 *
	 * @return string  the prefix
	 */
	public function getPrefix() {
		return $this->cachePrefix;
	}

	/**
	 * Returns the cache directory
	 *
	 * @return string  the absolute path to the cache directory
	 */
	public function getCacheDirectory() {
		return $this->cacheDir;
	}

	/**
	 * Returns the sqlite connection
	 *
	 * @return PDO  the established connection
	 */
	public function getSQLiteConnection() {
		return $this->sqliteConn;
	}

	/**
	 * Returns the sqlite table name
	 *
	 * @return string
	 */
	public function getSQLiteTableName() {
		return $this->sqliteTable;
	}

	/**
	 * Returns the MySQL connection
	 *
	 * @return PDO  the established connection
	 */
	public function getMySQLConnection() {
		return $this->mysqlConn;
	}

	/**
	 * Returns the MySQL table name
	 *
	 * @return string
	 */
	public function getMySQLTableName() {
		return $this->mysqlTable;
	}
}
