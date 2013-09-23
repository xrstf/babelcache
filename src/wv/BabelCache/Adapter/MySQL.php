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
 * MySQL Cache
 *
 * This PDO adapter uses a MySQL table to store the data.
 *
 * @package BabelCache.Adapter
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
		return
			class_exists('PDO') &&
			in_array('mysql', \PDO::getAvailableDrivers()) &&
			(!$factory || $factory->getMySQLTableName() !== null);
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
		// @codeCoverageIgnoreStart
		if (strpos($host, '/') !== false) {
			$dsn = 'mysql:unix_socket='.$host;
		}
		// @codeCoverageIgnoreEnd
		else {
			$parts = explode(':', $host);

			if (count($parts) === 1) {
				$dsn = 'mysql:host='.$host;
			}
			else {
				$dsn = sprintf('mysql:host=%s;port=%s', $parts[0], $parts[1]);
			}
		}

		if (!empty($database)) {
			$dsn .= ';dbname='.$database;
		}

		$dsn .= ';charset=utf8';

		return new \PDO($dsn, $user, $password, array(
			\PDO::ATTR_ERRMODE    => \PDO::ERRMODE_EXCEPTION,
			\PDO::ATTR_PERSISTENT => true
		));
	}

	protected function getQueries($table) {
		return array(
			'get'    => sprintf('SELECT payload FROM %s WHERE keyhash = :hash', $table),
			'set'    => sprintf('REPLACE INTO %s (keyhash, payload) VALUES (:hash, :payload)', $table),
			'exists' => sprintf('SELECT 1 FROM %s WHERE keyhash = :hash', $table),
			'delete' => sprintf('DELETE FROM %s WHERE keyhash = :hash', $table),
			'clear'  => sprintf('DELETE FROM %s', $table),
			'lock'   => sprintf('INSERT IGNORE INTO %s (keyhash, payload) VALUES (:hash, :payload)', $table)
		);
	}

	protected function supportsRawData() {
		return true;
	}
}
