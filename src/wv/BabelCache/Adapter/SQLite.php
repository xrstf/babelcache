<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace wv\BabelCache\Adapter;

use wv\BabelCache\Factory;

/**
 * SQLite Cache
 *
 * This is an improved fallback cache for systems with "special needs"
 * regarding the filesystem. To avoid problems with file permissions, stati and
 * all the other stuff that makes the PHP file API so goddamn awful, use this
 * implementation.
 *
 * @package BabelCache.Adapter
 */
class SQLite extends PDO {
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
		return
			class_exists('PDO') &&
			in_array('sqlite', \PDO::getAvailableDrivers()) &&
			(!$factory || $factory->getSQLiteTableName() !== null);
	}

	/**
	 * Connect to a database
	 *
	 * @param  string $databaseFile  full path to the database file
	 * @return \PDO                  the database connection instance
	 */
	public static function connect($databaseFile) {
		return new \PDO('sqlite:'.$databaseFile, null, null, array(
			\PDO::ATTR_ERRMODE    => \PDO::ERRMODE_EXCEPTION,
			\PDO::ATTR_PERSISTENT => true
		));
	}

	protected function getQueries($table) {
		return array(
			'get'    => sprintf('SELECT payload FROM %s WHERE keyhash = :hash', $table),
			'set'    => sprintf('INSERT OR REPLACE INTO %s (keyhash, payload) VALUES (:hash, :payload)', $table),
			'exists' => sprintf('SELECT 1 FROM %s WHERE keyhash = :hash', $table),
			'delete' => sprintf('DELETE FROM %s WHERE keyhash = :hash', $table),
			'clear'  => sprintf('DELETE FROM %s', $table),
			'lock'   => sprintf('INSERT OR IGNORE INTO %s (keyhash, payload) VALUES (:hash, :payload)', $table)
		);
	}

	protected function supportsRawData() {
		return false;
	}
}
