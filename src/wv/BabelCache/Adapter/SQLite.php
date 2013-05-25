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

use PDO;
use wv\BabelCache\AdapterInterface;
use wv\BabelCache\Exception;
use wv\BabelCache\Factory;
use wv\BabelCache\LockingInterface;

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
class SQLite implements AdapterInterface, LockingInterface {
	protected $pdo   = null;    ///< PDO    database connection
	protected $stmts = array(); ///< array  list of prepared statements

	/**
	 * Constructor
	 *
	 * @param PDO $connection  the already established connection
	 */
	public function __construct(PDO $connection) {
		$this->pdo = $connection;

		$stmt   = $this->pdo->query('SELECT * FROM sqlite_master WHERE type = "table" AND name = "babelcache_adapter"');
		$tables = $stmt->fetchAll();

		if (empty($tables)) {
			$sql = 'CREATE TABLE "babelcache_adapter" ("keyhash" VARCHAR(50), "payload" BLOB, PRIMARY KEY ("keyhash"))';
			$this->pdo->exec($sql);
		}

		$stmt->closeCursor();
	}

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
		return in_array('sqlite', PDO::getAvailableDrivers());
	}

	/**
	 * Connect to a database
	 *
	 * @param  string $databaseFile  full path to the database file
	 * @return PDO                   the database connection instance
	 */
	public static function connect($databaseFile) {
		return new PDO('sqlite:'.$databaseFile, null, null, array(
			PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_PERSISTENT => true
		));
	}

	/**
	 * Gets a value out of the cache
	 *
	 * This method will try to read the value from the cache. If it's not found,
	 * $default will be returned.
	 *
	 * @param  string  $key    the object key
	 * @param  boolean $found  will be set to true or false when the method is finished
	 * @return mixed           the found value or null
	 */
	public function get($key, &$found = null) {
		$found = false;
		$stmt  = $this->getStatement('select');

		$stmt->execute(array('hash' => sha1($key)));

		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$stmt->closeCursor();

		$found = !empty($row) && !empty($row['payload']);

		return $found ? unserialize(base64_decode($row['payload'])) : null;
	}

	/**
	 * Sets a value
	 *
	 * This method will put a value into the cache. If it already exists, it
	 * will be overwritten.
	 *
	 * @param  string $key    the object key
	 * @param  mixed  $value  the value to store
	 * @return boolean        true on success, else false
	 */
	public function set($key, $value) {
		$stmt    = $this->getStatement('replace');
		$payload = base64_encode(serialize($value));

		// update/insert data
		$stmt->execute(array('hash' => sha1($key), 'payload' => $payload));
		$stmt->closeCursor();

		return true;
	}

	/**
	 * Checks whether a value exists
	 *
	 * @param  string $key  the object key
	 * @return boolean      true if the value exists, else false
	 */
	public function exists($key) {
		$stmt = $this->getStatement('select');
		$stmt->execute(array('hash' => sha1($key)));

		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$stmt->closeCursor();

		return !empty($row);
	}

	/**
	 * Removes a single value from the cache
	 *
	 * @param  string $key  the object key
	 * @return boolean      true if the value was deleted, else false
	 */
	public function remove($key) {
		$stmt = $this->getStatement('delete');
		$stmt->execute(array('hash' => sha1($key)));
		$stmt->closeCursor();

		return $stmt->rowCount() > 0;
	}

	/**
	 * Removes all values
	 *
	 * @return boolean  true if the flush was successful, else false
	 */
	public function clear() {
		$stmt = $this->getStatement('clear');
		$stmt->execute();

		return true;
	}

	/**
	 * Locks a key
	 *
	 * This method will create a lock for a specific key.
	 *
	 * @param  string $key  the key
	 * @return boolean      true if the lock was aquired, else false
	 */
	public function lock($key) {
		$stmt = $this->getStatement('insert');
		$stmt->execute(array('hash' => 'lock:'.sha1($key), 'payload' => ''));
		$stmt->closeCursor();

		return $stmt->rowCount() > 0;
	}

	/**
	 * Releases a lock
	 *
	 * This method will remove a lock for a specific key.
	 *
	 * @param  string $key  the key
	 * @return boolean      true if the lock was released or there was no lock, else false
	 */
	public function unlock($key) {
		$stmt = $this->getStatement('delete');
		$stmt->execute(array('hash' => 'lock:'.sha1($key)));
		$stmt->closeCursor();

		return $stmt->rowCount() > 0;
	}

	protected function getStatement($key) {
		$where   = '"keyhash" = :hash';
		$queries = array(
			'insert'  => 'INSERT OR IGNORE INTO "babelcache_adapter" ("keyhash", "payload") VALUES (:hash,:payload)',
			'replace' => 'INSERT OR REPLACE INTO "babelcache_adapter" ("keyhash", "payload") VALUES (:hash,:payload)',
			'select'  => 'SELECT "payload" FROM "babelcache_adapter" WHERE '.$where,
			'delete'  => 'DELETE FROM "babelcache_adapter" WHERE '.$where,
			'clear'   => 'DELETE FROM "babelcache_adapter"',
		);

		if (!isset($this->stmts[$key])) {
			if (!isset($queries[$key])) {
				throw new Exception('Cannot find query for key '.$key.'.');
			}

			$this->stmts[$key] = $this->pdo->prepare($queries[$key]);
		}

		return $this->stmts[$key];
	}
}
