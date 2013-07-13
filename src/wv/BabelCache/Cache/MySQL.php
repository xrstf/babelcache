<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace wv\BabelCache\Cache;

use wv\BabelCache\Factory;
use wv\BabelCache\Adapter\MySQL as MySQLAdapter;

/**
 * MySQL Cache
 *
 * This PDO cache uses a MySQL table to store the data.
 *
 * @package BabelCache.Cache
 */
class MySQL extends PDO {
	/**
	 * Checks whether a caching system is avilable
	 *
	 * This method will be called before an instance is created. It is supposed
	 * to check for the required functions and whether user data caching is
	 * enabled.
	 *
	 * @param  Factory $factory  the project's factory to give the adapter some more knowledge
	 * @return boolean           true if the cache can be used, else false
	 */
	public static function isAvailable(Factory $factory = null) {
		return MySQLAdapter::isAvailable($factory);
	}

	/**
	 * Connect to a database
	 *
	 * @param  string $host
	 * @param  string $user
	 * @param  string $password
	 * @param  string $database
	 * @return \PDO              the database connection instance
	 */
	public static function connect($host, $user, $password, $database) {
		return MySQLAdapter::connect($host, $user, $password, $database);
	}

	protected function getQueries($table) {
		return array(
			'get'    => sprintf('SELECT payload FROM %s WHERE prefix = :prefix AND namespace = :namespace AND keyname = :key', $table),
			'set'    => sprintf('REPLACE INTO %s (prefix, namespace, keyname, payload) VALUES (:prefix, :namespace, :key, :payload)', $table),
			'exists' => sprintf('SELECT 1 FROM %s WHERE prefix = :prefix AND namespace = :namespace AND keyname = :key', $table),
			'delete' => sprintf('DELETE FROM %s WHERE prefix = :prefix AND namespace = :namespace AND keyname = :key', $table),
			'clear'  => sprintf('DELETE FROM %s WHERE prefix = :prefix AND namespace = :namespace', $table),
			'rclear' => sprintf('DELETE FROM %s WHERE prefix = :prefix AND (namespace = :namespace OR namespace LIKE :nslike)', $table),
			'lclear' => sprintf('DELETE FROM %s WHERE prefix = :prefix AND keyname LIKE :key', $table),
			'lock'   => sprintf('INSERT IGNORE INTO %s (prefix, namespace, keyname, payload) VALUES (:prefix, :namespace, :key, "")', $table),
		);
	}

	protected function supportsRawData() {
		return true;
	}
}
