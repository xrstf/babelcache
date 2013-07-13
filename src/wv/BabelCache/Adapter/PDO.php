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

use wv\BabelCache\AdapterInterface;
use wv\BabelCache\Exception;
use wv\BabelCache\LockingInterface;

/**
 * PDO Cache
 *
 * This adapter will use a PDO database to cache data. The DBMS specific DDL
 * statements can be found in the db-schema directory.
 *
 * @package BabelCache.Adapter
 */
abstract class PDO implements AdapterInterface, LockingInterface {
	protected $pdo     = null;    ///< PDO     database connection
	protected $table   = null;    ///< string  table name
	protected $queries = array(); ///< array   list of queries
	protected $stmts   = array(); ///< array   list of prepared statements

	/**
	 * Constructor
	 *
	 * @param PDO    $connection  the already established connection
	 * @param string $tableName
	 */
	public function __construct(\PDO $connection, $tableName) {
		$this->pdo     = $connection;
		$this->table   = $tableName;
		$this->queries = $this->getQueries($tableName);
	}

	abstract protected function getQueries($table);
	abstract protected function supportsRawData();

	/**
	 * Gets a value out of the cache
	 *
	 * This method will try to read the value from the cache.
	 *
	 * @param  string  $key    the object key
	 * @param  boolean $found  will be set to true or false when the method is finished
	 * @return mixed           the found value or null
	 */
	public function get($key, &$found = null) {
		$found = false;
		$stmt  = $this->getStatement('get');

		$stmt->execute(array('hash' => sha1($key)));

		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		$stmt->closeCursor();

		$found = !empty($row) && !empty($row['payload']);

		if (!$found) {
			return null;
		}

		$payload = $this->supportsRawData() ? $row['payload'] : base64_decode($row['payload']);

		return unserialize($payload);
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
		$stmt    = $this->getStatement('set');
		$payload = serialize($value);

		if (!$this->supportsRawData()) {
			$payload = base64_encode($payload);
		}

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
		$stmt = $this->getStatement('exists');
		$stmt->execute(array('hash' => sha1($key)));

		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		$stmt->closeCursor();

		return !empty($row);
	}

	/**
	 * Deletes a single value from the cache
	 *
	 * @param  string $key  the object key
	 * @return boolean      true if the value was deleted, else false
	 */
	public function delete($key) {
		$stmt = $this->getStatement('delete');
		$stmt->execute(array('hash' => sha1($key)));
		$stmt->closeCursor();

		return $stmt->rowCount() > 0;
	}

	/**
	 * Deletes all values
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
		$stmt = $this->getStatement('lock');
		$stmt->execute(array('hash' => 'lock:'.sha1($key), 'payload' => ''));
		$stmt->closeCursor();

		return $stmt->rowCount() > 0;
	}

	/**
	 * Releases a lock
	 *
	 * This method will delete a lock for a specific key.
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

	/**
	 * Check if a key is locked
	 *
	 * @param  string $key  the key
	 * @return boolean      true if the key is locked, else false
	 */
	public function hasLock($key) {
		$stmt = $this->getStatement('exists');
		$stmt->execute(array('hash' => 'lock:'.sha1($key)));

		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		$stmt->closeCursor();

		return !empty($row);
	}

	protected function getStatement($key) {
		if (!isset($this->stmts[$key])) {
			if (!isset($this->queries[$key])) {
				throw new Exception('Cannot find query for key '.$key.'.');
			}

			$this->stmts[$key] = $this->pdo->prepare($this->queries[$key]);
		}

		return $this->stmts[$key];
	}
}
